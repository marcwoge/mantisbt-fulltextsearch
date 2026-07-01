<?php
# FullTextSearch - Full-text search plugin for MantisBT
# Copyright (C) marcwoge <marc.woge@googlemail.com>
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License version 2 as
# published by the Free Software Foundation.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
#
# SPDX-License-Identifier: GPL-2.0-only

/**
 * Internal helper API for the FullTextSearch plugin.
 *
 * Responsibilities:
 *   - parse the raw query string into tokens / phrases
 *   - build and run the database queries against the searched tables
 *   - apply access-control and project-scope filtering
 *   - assemble per-bug result rows together with match snippets
 *
 * Only MantisBT's database abstraction (db_query / db_param /
 * db_prepare_string) is used - no raw mysqli_* or PDO calls.
 */

if( !defined( 'MANTIS_VERSION' ) ) {
	# Defensive fallback: this file is normally included by a plugin page that
	# has already bootstrapped MantisBT core. If included standalone, load core
	# from the MantisBT root (four levels up: api -> FullTextSearch -> plugins
	# -> MantisBT root).
	require_once( dirname( __FILE__, 4 ) . '/core.php' );
}

/**
 * Maximum length of a single match snippet (characters) before truncation.
 */
define( 'FTS_SNIPPET_MAX', 120 );

/**
 * Parse a raw user query into an array of search terms.
 *
 * Whitespace separated tokens become individual terms (ANDed by the caller).
 * Substrings wrapped in double quotes are kept together as a single phrase
 * term, so `"login error" timeout` yields two terms: `login error` and
 * `timeout`.
 *
 * @param string $p_query Raw query string from the search form.
 * @return array Array of non-empty term strings.
 */
function fts_parse_terms( $p_query ) {
	$t_terms = array();

	# Extract quoted phrases first, then fall back to whitespace tokens.
	if( preg_match_all( '/"([^"]+)"/', $p_query, $t_matches ) ) {
		foreach( $t_matches[1] as $t_phrase ) {
			$t_phrase = trim( $t_phrase );
			if( $t_phrase !== '' ) {
				$t_terms[] = $t_phrase;
			}
		}
		# Remove the quoted parts so they are not tokenised again.
		$p_query = preg_replace( '/"[^"]+"/', ' ', $p_query );
	}

	foreach( preg_split( '/\s+/', trim( (string)$p_query ) ) as $t_token ) {
		if( $t_token !== '' ) {
			$t_terms[] = $t_token;
		}
	}

	return $t_terms;
}

/**
 * Escape a literal term for safe use inside a SQL LIKE pattern.
 *
 * Escapes the LIKE wildcard characters (% and _) as well as the backslash
 * escape character itself, then wraps the term in %...% for a substring match.
 *
 * @param string $p_term Literal search term.
 * @return string LIKE pattern (already wrapped in wildcards).
 */
function fts_like_pattern( $p_term ) {
	$t_escaped = str_replace(
		array( '\\', '%', '_' ),
		array( '\\\\', '\\%', '\\_' ),
		$p_term
	);
	return '%' . $t_escaped . '%';
}

/**
 * Return the list of field source keys that are enabled for searching.
 *
 * @param array $p_options Search options (cb_summary, cb_description, ...).
 * @return array Map of source-key => bool.
 */
function fts_active_sources( array $p_options ) {
	return array(
		'summary'       => !empty( $p_options['summary'] ),
		'description'   => !empty( $p_options['description'] ),
		'custom_fields' => !empty( $p_options['custom_fields'] ),
		'bugnotes'      => !empty( $p_options['bugnotes'] ),
	);
}

/**
 * Execute the full-text search and return matching bug IDs (unfiltered by ACL).
 *
 * For every term a sub-query collects the set of bug IDs that contain the term
 * in any of the active sources. The result is the intersection of those sets
 * (logical AND across terms), restricted to the requested project and status
 * filters.
 *
 * @param array $p_terms   Parsed search terms (each ANDed).
 * @param array $p_options Search options.
 * @param int   $p_project_id Project id, or ALL_PROJECTS for every project.
 * @param array $p_statuses   Status codes to include (empty = all statuses).
 * @return array Ordered list of distinct bug IDs (most recently updated first).
 */
function fts_find_bug_ids( array $p_terms, array $p_options, $p_project_id, array $p_statuses ) {
	$t_bug_table              = db_get_table( 'bug' );
	$t_bug_text_table         = db_get_table( 'bug_text' );
	$t_custom_string_table    = db_get_table( 'custom_field_string' );
	$t_bugnote_table          = db_get_table( 'bugnote' );
	$t_bugnote_text_table     = db_get_table( 'bugnote_text' );

	$t_sources = fts_active_sources( $p_options );

	# Nothing to search in - bail out early.
	if( !in_array( true, $t_sources, true ) ) {
		return array();
	}

	# Whether private bugnotes may participate in the search at all. When the
	# user is not allowed to see private notes they are excluded from the SQL
	# match too, otherwise a private note could leak its bug as a phantom hit
	# (the snippet would be hidden but the issue would still appear).
	$t_can_search_private = fts_user_can_search_private_notes( $p_project_id );
	$t_current_user_id    = auth_get_current_user_id();

	$t_matched_sets = array();

	foreach( $p_terms as $t_term ) {
		$t_params  = array();
		$t_clauses = array();

		# --- mantis_bug_table.summary ------------------------------------
		if( $t_sources['summary'] ) {
			$t_clauses[] = 'b.id IN ( SELECT bs.id FROM ' . $t_bug_table . ' bs'
				. ' WHERE bs.summary LIKE ' . db_param() . ' )';
			$t_params[] = fts_like_pattern( $t_term );
		}

		# --- mantis_bug_text_table (description, steps, additional) ------
		if( $t_sources['description'] ) {
			$t_clauses[] = 'b.id IN ( SELECT bt2.id FROM ' . $t_bug_table . ' bt2'
				. ' JOIN ' . $t_bug_text_table . ' txt ON txt.id = bt2.bug_text_id'
				. ' WHERE txt.description LIKE ' . db_param()
				. ' OR txt.steps_to_reproduce LIKE ' . db_param()
				. ' OR txt.additional_information LIKE ' . db_param() . ' )';
			$t_params[] = fts_like_pattern( $t_term );
			$t_params[] = fts_like_pattern( $t_term );
			$t_params[] = fts_like_pattern( $t_term );
		}

		# --- mantis_custom_field_string_table.value ----------------------
		if( $t_sources['custom_fields'] ) {
			$t_clauses[] = 'b.id IN ( SELECT cfs.bug_id FROM ' . $t_custom_string_table . ' cfs'
				. ' WHERE cfs.value LIKE ' . db_param() . ' )';
			$t_params[] = fts_like_pattern( $t_term );
		}

		# --- mantis_bugnote_text_table.note ------------------------------
		if( $t_sources['bugnotes'] ) {
			$t_note_sql = 'b.id IN ( SELECT bn.bug_id FROM ' . $t_bugnote_table . ' bn'
				. ' JOIN ' . $t_bugnote_text_table . ' bnt ON bnt.id = bn.bugnote_text_id'
				. ' WHERE bnt.note LIKE ' . db_param();
			$t_params[] = fts_like_pattern( $t_term );

			if( !$t_can_search_private ) {
				# Restrict to public notes and notes authored by the user.
				$t_note_sql .= ' AND ( bn.view_state = ' . VS_PUBLIC
					. ' OR bn.reporter_id = ' . db_param() . ' )';
				$t_params[] = (int)$t_current_user_id;
			}

			$t_note_sql .= ' )';
			$t_clauses[] = $t_note_sql;
		}

		$t_where = array( '( ' . implode( ' OR ', $t_clauses ) . ' )' );

		# Project scope.
		if( (int)$p_project_id != ALL_PROJECTS ) {
			$t_where[]  = 'b.project_id = ' . db_param();
			$t_params[] = (int)$p_project_id;
		}

		# Status filter.
		if( !empty( $p_statuses ) ) {
			$t_status_params = array();
			foreach( $p_statuses as $t_status ) {
				$t_status_params[] = db_param();
				$t_params[]        = (int)$t_status;
			}
			$t_where[] = 'b.status IN ( ' . implode( ', ', $t_status_params ) . ' )';
		}

		$t_query = 'SELECT DISTINCT b.id FROM ' . $t_bug_table . ' b'
			. ' WHERE ' . implode( ' AND ', $t_where );

		$t_result = db_query( $t_query, $t_params );

		$t_ids = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_ids[(int)$t_row['id']] = true;
		}

		# A term with no matches means the AND result is empty.
		if( empty( $t_ids ) ) {
			return array();
		}

		$t_matched_sets[] = $t_ids;
	}

	if( empty( $t_matched_sets ) ) {
		return array();
	}

	# Intersect the per-term ID sets (logical AND across all terms).
	$t_intersection = array_shift( $t_matched_sets );
	foreach( $t_matched_sets as $t_set ) {
		$t_intersection = array_intersect_key( $t_intersection, $t_set );
		if( empty( $t_intersection ) ) {
			return array();
		}
	}

	$t_bug_ids = array_keys( $t_intersection );

	# Order the surviving IDs by last update (most recent first) for stable
	# pagination. Done in a second pass to keep the search query portable.
	return fts_sort_ids_by_update( $t_bug_ids );
}

/**
 * Sort a list of bug IDs by their last-updated timestamp, descending.
 *
 * @param array $p_bug_ids List of bug IDs.
 * @return array Sorted list of bug IDs.
 */
function fts_sort_ids_by_update( array $p_bug_ids ) {
	if( empty( $p_bug_ids ) ) {
		return array();
	}

	$t_bug_table = db_get_table( 'bug' );

	$t_params       = array();
	$t_placeholders = array();
	foreach( $p_bug_ids as $t_id ) {
		$t_placeholders[] = db_param();
		$t_params[]       = (int)$t_id;
	}

	$t_query = 'SELECT id FROM ' . $t_bug_table
		. ' WHERE id IN ( ' . implode( ', ', $t_placeholders ) . ' )'
		. ' ORDER BY last_updated DESC, id DESC';

	$t_result = db_query( $t_query, $t_params );

	$t_sorted = array();
	while( $t_row = db_fetch_array( $t_result ) ) {
		$t_sorted[] = (int)$t_row['id'];
	}

	return $t_sorted;
}

/**
 * Filter a list of bug IDs down to those the current user may view.
 *
 * Applies MantisBT's standard per-bug access-level check.
 *
 * @param array $p_bug_ids List of candidate bug IDs.
 * @return array List of viewable bug IDs (original order preserved).
 */
function fts_filter_viewable( array $p_bug_ids ) {
	$t_viewable = array();
	foreach( $p_bug_ids as $t_bug_id ) {
		if( bug_exists( $t_bug_id ) && access_has_bug_level( config_get( 'view_bug_threshold' ), $t_bug_id ) ) {
			$t_viewable[] = (int)$t_bug_id;
		}
	}
	return $t_viewable;
}

/**
 * Determine whether the current user may see private bugnotes on a bug.
 *
 * @param int $p_bug_id Bug id.
 * @return bool True if private bugnotes should be included.
 */
function fts_can_view_private_bugnotes( $p_bug_id ) {
	return access_has_bug_level( config_get( 'private_bugnote_threshold' ), $p_bug_id );
}

/**
 * Decide whether private bugnotes may take part in the SQL search.
 *
 * For a single-project search this is a direct project-level check. For an
 * "All Projects" search the result is true only when the user meets the
 * private-bugnote threshold in *every* project they can access; this is
 * deliberately conservative so a private note can never leak a bug as a
 * phantom hit. The per-bug display filter (fts_can_view_private_bugnotes)
 * still applies on top of this when rendering snippets.
 *
 * Note: per-project threshold overrides combined with an "All Projects"
 * search may therefore hide private-note matches that the user could in fact
 * see in some projects - see the README "Known Limitations" section.
 *
 * @param int $p_project_id Project id or ALL_PROJECTS.
 * @return bool True if private notes may be searched.
 */
function fts_user_can_search_private_notes( $p_project_id ) {
	$t_threshold = config_get( 'private_bugnote_threshold' );

	if( (int)$p_project_id != ALL_PROJECTS ) {
		return access_has_project_level( $t_threshold, (int)$p_project_id );
	}

	foreach( current_user_get_accessible_projects() as $t_pid ) {
		if( !access_has_project_level( $t_threshold, (int)$t_pid ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Build the highlighted, truncated match snippets for a single bug.
 *
 * Re-reads the relevant field values for the bug and, for each field that
 * actually contains one of the search terms, produces a snippet centred on
 * the first match, truncated to FTS_SNIPPET_MAX characters and with the
 * matched terms wrapped in <mark> tags.
 *
 * @param int   $p_bug_id  Bug id.
 * @param array $p_terms   Parsed search terms.
 * @param array $p_options Search options (active sources).
 * @return array List of [ 'field' => label-key, 'snippet' => html ] entries.
 */
function fts_build_snippets( $p_bug_id, array $p_terms, array $p_options ) {
	$t_sources = fts_active_sources( $p_options );
	$t_snippets = array();

	$t_bug_table          = db_get_table( 'bug' );
	$t_bug_text_table     = db_get_table( 'bug_text' );
	$t_custom_string_table = db_get_table( 'custom_field_string' );
	$t_custom_field_table = db_get_table( 'custom_field' );
	$t_bugnote_table      = db_get_table( 'bugnote' );
	$t_bugnote_text_table = db_get_table( 'bugnote_text' );

	# --- Summary ---------------------------------------------------------
	if( $t_sources['summary'] ) {
		$t_query  = 'SELECT summary FROM ' . $t_bug_table . ' WHERE id = ' . db_param();
		$t_result = db_query( $t_query, array( (int)$p_bug_id ) );
		if( $t_row = db_fetch_array( $t_result ) ) {
			$t_snippet = fts_make_snippet( $t_row['summary'], $p_terms );
			if( $t_snippet !== null ) {
				$t_snippets[] = array( 'field' => 'field_summary', 'snippet' => $t_snippet );
			}
		}
	}

	# --- Description / steps / additional information --------------------
	if( $t_sources['description'] ) {
		$t_query = 'SELECT txt.description, txt.steps_to_reproduce, txt.additional_information'
			. ' FROM ' . $t_bug_table . ' b'
			. ' JOIN ' . $t_bug_text_table . ' txt ON txt.id = b.bug_text_id'
			. ' WHERE b.id = ' . db_param();
		$t_result = db_query( $t_query, array( (int)$p_bug_id ) );
		if( $t_row = db_fetch_array( $t_result ) ) {
			foreach( array(
				'description'            => 'field_description',
				'steps_to_reproduce'     => 'field_steps',
				'additional_information' => 'field_additional',
			) as $t_col => $t_label ) {
				$t_snippet = fts_make_snippet( $t_row[$t_col], $p_terms );
				if( $t_snippet !== null ) {
					$t_snippets[] = array( 'field' => $t_label, 'snippet' => $t_snippet );
				}
			}
		}
	}

	# --- Custom field values --------------------------------------------
	if( $t_sources['custom_fields'] ) {
		$t_query = 'SELECT cf.name, cfs.value'
			. ' FROM ' . $t_custom_string_table . ' cfs'
			. ' JOIN ' . $t_custom_field_table . ' cf ON cf.id = cfs.field_id'
			. ' WHERE cfs.bug_id = ' . db_param();
		$t_result = db_query( $t_query, array( (int)$p_bug_id ) );
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_snippet = fts_make_snippet( $t_row['value'], $p_terms );
			if( $t_snippet !== null ) {
				$t_snippets[] = array(
					'field'   => 'field_custom',
					'label'   => $t_row['name'],
					'snippet' => $t_snippet,
				);
			}
		}
	}

	# --- Bugnotes (respecting private-note access) ----------------------
	if( $t_sources['bugnotes'] ) {
		$t_allow_private = fts_can_view_private_bugnotes( $p_bug_id );

		$t_query = 'SELECT bn.view_state, bnt.note'
			. ' FROM ' . $t_bugnote_table . ' bn'
			. ' JOIN ' . $t_bugnote_text_table . ' bnt ON bnt.id = bn.bugnote_text_id'
			. ' WHERE bn.bug_id = ' . db_param()
			. ' ORDER BY bn.date_submitted ASC';
		$t_result = db_query( $t_query, array( (int)$p_bug_id ) );
		while( $t_row = db_fetch_array( $t_result ) ) {
			if( (int)$t_row['view_state'] == VS_PRIVATE && !$t_allow_private ) {
				continue;
			}
			$t_snippet = fts_make_snippet( $t_row['note'], $p_terms );
			if( $t_snippet !== null ) {
				$t_snippets[] = array( 'field' => 'field_bugnote', 'snippet' => $t_snippet );
			}
		}
	}

	return $t_snippets;
}

/**
 * Produce a highlighted snippet from a field value for the given terms.
 *
 * Returns null when none of the terms occur in the value. Otherwise the
 * snippet is centred on the first match, truncated to FTS_SNIPPET_MAX
 * characters (with leading/trailing ellipses where content was cut) and all
 * matched terms are wrapped in <mark> tags. Output is HTML-escaped before the
 * <mark> tags are inserted, so it is safe to print as-is.
 *
 * @param string $p_value Raw field value.
 * @param array  $p_terms Parsed search terms.
 * @return string|null Highlighted HTML snippet, or null if no term matched.
 */
function fts_make_snippet( $p_value, array $p_terms ) {
	$t_value = (string)$p_value;
	if( $t_value === '' ) {
		return null;
	}

	# Find the earliest match position across all terms (case-insensitive).
	$t_first_pos = null;
	foreach( $p_terms as $t_term ) {
		$t_pos = mb_stripos( $t_value, $t_term );
		if( $t_pos !== false && ( $t_first_pos === null || $t_pos < $t_first_pos ) ) {
			$t_first_pos = $t_pos;
		}
	}

	if( $t_first_pos === null ) {
		return null;
	}

	# Build a window of FTS_SNIPPET_MAX characters around the first match.
	$t_len    = mb_strlen( $t_value );
	$t_window = FTS_SNIPPET_MAX;
	$t_start  = (int)max( 0, $t_first_pos - (int)floor( $t_window / 3 ) );

	$t_prefix = $t_start > 0 ? "\xE2\x80\xA6" : '';
	$t_chunk  = mb_substr( $t_value, $t_start, $t_window );
	$t_suffix = ( $t_start + $t_window ) < $t_len ? "\xE2\x80\xA6" : '';

	# Escape first, then highlight, so user content cannot inject markup.
	$t_escaped = string_html_specialchars( $t_chunk );
	$t_escaped = fts_highlight( $t_escaped, $p_terms );

	return string_html_specialchars( $t_prefix ) . $t_escaped . string_html_specialchars( $t_suffix );
}

/**
 * Wrap every occurrence of any search term in <mark> tags.
 *
 * Operates on already HTML-escaped text. Terms are matched case-insensitively
 * and the original casing of the text is preserved inside the <mark> tag.
 *
 * @param string $p_escaped_text Already HTML-escaped text.
 * @param array  $p_terms        Parsed search terms.
 * @return string Text with matches wrapped in <mark>...</mark>.
 */
function fts_highlight( $p_escaped_text, array $p_terms ) {
	$t_text = $p_escaped_text;

	# Longer terms first so phrases win over their constituent words.
	$t_terms = $p_terms;
	usort( $t_terms, function( $a, $b ) {
		return mb_strlen( $b ) - mb_strlen( $a );
	} );

	foreach( $t_terms as $t_term ) {
		$t_term = trim( $t_term );
		if( $t_term === '' ) {
			continue;
		}
		# The text is HTML-escaped, so escape the term the same way before
		# matching, then guard the regex special characters.
		$t_needle = preg_quote( string_html_specialchars( $t_term ), '/' );
		$t_text = preg_replace_callback(
			'/(' . $t_needle . ')/iu',
			function( $p_match ) {
				return '<mark>' . $p_match[1] . '</mark>';
			},
			$t_text
		);
	}

	return $t_text;
}

/**
 * Run a full search and return a structured result set ready for display.
 *
 * @param string $p_query      Raw query string.
 * @param array  $p_options    Search options (summary/description/...).
 * @param int    $p_project_id Project id or ALL_PROJECTS.
 * @param array  $p_statuses   Status codes (empty = all).
 * @param int    $p_page       1-based page number.
 * @param int    $p_per_page   Results per page.
 * @return array {
 *     @type array $terms      Parsed terms.
 *     @type int   $total      Total viewable matches.
 *     @type int   $page       Current page (clamped).
 *     @type int   $page_count Total number of pages.
 *     @type array $rows       Result rows for the current page.
 * }
 */
function fts_search( $p_query, array $p_options, $p_project_id, array $p_statuses, $p_page, $p_per_page ) {
	$t_terms = fts_parse_terms( $p_query );

	$t_result = array(
		'terms'      => $t_terms,
		'total'      => 0,
		'page'       => 1,
		'page_count' => 0,
		'rows'       => array(),
	);

	if( empty( $t_terms ) ) {
		return $t_result;
	}

	$t_candidate_ids = fts_find_bug_ids( $t_terms, $p_options, $p_project_id, $p_statuses );
	$t_viewable_ids  = fts_filter_viewable( $t_candidate_ids );

	$t_total = count( $t_viewable_ids );
	$t_result['total'] = $t_total;

	if( $t_total == 0 ) {
		return $t_result;
	}

	$t_per_page   = max( 1, (int)$p_per_page );
	$t_page_count = (int)ceil( $t_total / $t_per_page );
	$t_page       = min( max( 1, (int)$p_page ), $t_page_count );

	$t_offset   = ( $t_page - 1 ) * $t_per_page;
	$t_page_ids = array_slice( $t_viewable_ids, $t_offset, $t_per_page );

	$t_rows = array();
	foreach( $t_page_ids as $t_bug_id ) {
		$t_bug = bug_get( $t_bug_id, false );
		$t_rows[] = array(
			'id'            => $t_bug_id,
			'project_id'    => $t_bug->project_id,
			'project_name'  => project_get_name( $t_bug->project_id ),
			'status'        => $t_bug->status,
			'summary'       => $t_bug->summary,
			'reporter_id'   => $t_bug->reporter_id,
			'reporter_name' => user_get_name( $t_bug->reporter_id ),
			'last_updated'  => $t_bug->last_updated,
			'snippets'      => fts_build_snippets( $t_bug_id, $t_terms, $p_options ),
		);
	}

	$t_result['page']       = $t_page;
	$t_result['page_count'] = $t_page_count;
	$t_result['rows']       = $t_rows;

	return $t_result;
}
