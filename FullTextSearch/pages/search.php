<?php
# FullTextSearch - Full-text search plugin for MantisBT
# Copyright (c) 2026 Marc-Philipp Woge
#
# Licensed under the MIT License. See the LICENSE file in the project root
# for the full license text.
#
# SPDX-License-Identifier: MIT

/**
 * FullTextSearch plugin - search form and results page.
 *
 * Submits via GET so result URLs are bookmarkable and shareable. MantisBT
 * routes plugin pages through plugin.php?page=FullTextSearch/search, so the
 * route is carried as a hidden field (a GET form drops the action query
 * string). Pagination uses a dedicated "page_num" parameter to avoid clashing
 * with the routing "page" parameter.
 */

require_once( dirname( __FILE__, 2 ) . '/api/search_api.php' );

# Must be logged in to search.
auth_ensure_user_authenticated();

# --- Read request parameters --------------------------------------------------

$f_query      = gpc_get_string( 'q', '' );
$f_project_id = gpc_get_int( 'project_id', helper_get_current_project() );
$f_per_page   = gpc_get_int( 'per_page', 25 );
$f_page_num   = gpc_get_int( 'page_num', 1 );
$f_statuses   = gpc_get_int_array( 'status', array() );

# Whether the form has been submitted (used to distinguish "first load,
# everything checked by default" from "submitted with some boxes unchecked").
$f_submitted = gpc_get_bool( 'fts_submit', false );

if( $f_submitted ) {
	$t_options = array(
		'summary'       => gpc_get_bool( 'summary', false ),
		'description'   => gpc_get_bool( 'description', false ),
		'custom_fields' => gpc_get_bool( 'custom_fields', false ),
		'bugnotes'      => gpc_get_bool( 'bugnotes', false ),
	);
} else {
	# Default: search everywhere.
	$t_options = array(
		'summary'       => true,
		'description'   => true,
		'custom_fields' => true,
		'bugnotes'      => true,
	);
}

# Normalise per-page to one of the allowed values.
$t_allowed_per_page = array( 25, 50, 100 );
if( !in_array( $f_per_page, $t_allowed_per_page, true ) ) {
	$f_per_page = 25;
}

# --- Run the search (only when a non-empty query is present) ------------------

$t_query_trimmed = trim( $f_query );
$t_has_query     = ( $f_submitted && $t_query_trimmed !== '' );
$t_empty_query   = ( $f_submitted && $t_query_trimmed === '' );

$t_results = null;
$t_error   = null;

if( $t_has_query ) {
	try {
		$t_results = fts_search(
			$f_query,
			$t_options,
			$f_project_id,
			$f_statuses,
			$f_page_num,
			$f_per_page
		);
	} catch( Exception $e ) {
		$t_error = plugin_lang_get( 'plugin_fulltextsearch_error_db' );
	}
}

# --- Render -------------------------------------------------------------------

layout_page_header( plugin_lang_get( 'plugin_fulltextsearch_page_title' ) );
layout_page_begin();

# Status enum, used by both the filter and the result badges.
$t_status_enum = MantisEnum::getAssocArrayIndexedByValues( config_get( 'status_enum_string' ) );

# Accessible projects for the project selector.
$t_project_ids = current_user_get_accessible_projects();

# Canonical route for this plugin page.
$t_route = 'FullTextSearch/search';
?>
<style>
mark { background: #fff176; padding: 0 2px; }
.fts-snippet { display: block; margin: 2px 0; }
.fts-snippet-label { font-weight: bold; }
</style>

<div class="col-md-12 col-xs-12">
<div class="space-10"></div>

<form id="fts-search-form" method="get" action="<?php echo string_attribute( helper_mantis_url( 'plugin.php' ) ); ?>">
	<input type="hidden" name="page" value="<?php echo string_attribute( $t_route ); ?>"/>
	<input type="hidden" name="fts_submit" value="1"/>

	<div class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">
				<i class="ace-icon fa fa-search"></i>
				<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_page_title' ) ); ?>
			</h4>
		</div>

		<div class="widget-body">
			<div class="widget-main no-padding">
				<div class="table-responsive">
					<table class="table table-bordered table-condensed table-striped">
						<tr>
							<th class="category" width="20%">
								<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_menu_label' ) ); ?>
							</th>
							<td>
								<input type="text" name="q" class="input-sm"
									size="60" maxlength="255"
									placeholder="<?php echo string_attribute( plugin_lang_get( 'plugin_fulltextsearch_search_placeholder' ) ); ?>"
									value="<?php echo string_attribute( $f_query ); ?>"/>
								<p class="help-block">
									<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_tip_phrase_search' ) ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th class="category">
								<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_label_project' ) ); ?>
							</th>
							<td>
								<select name="project_id" class="input-sm">
									<option value="<?php echo ALL_PROJECTS; ?>"
										<?php check_selected( (int)$f_project_id, ALL_PROJECTS ); ?>>
										<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_label_all_projects' ) ); ?>
									</option>
									<?php foreach( $t_project_ids as $t_pid ) { ?>
										<option value="<?php echo (int)$t_pid; ?>"
											<?php check_selected( (int)$f_project_id, (int)$t_pid ); ?>>
											<?php echo string_display_line( project_get_name( $t_pid ) ); ?>
										</option>
									<?php } ?>
								</select>
							</td>
						</tr>

						<tr>
							<th class="category">
								<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_label_search_in' ) ); ?>
							</th>
							<td>
								<label class="inline">
									<input type="checkbox" class="ace" name="summary" value="1"
										<?php check_checked( (bool)$t_options['summary'] ); ?>/>
									<span class="lbl">&#160;<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_cb_summary' ) ); ?></span>
								</label>
								&#160;&#160;
								<label class="inline">
									<input type="checkbox" class="ace" name="description" value="1"
										<?php check_checked( (bool)$t_options['description'] ); ?>/>
									<span class="lbl">&#160;<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_cb_description' ) ); ?></span>
								</label>
								&#160;&#160;
								<label class="inline">
									<input type="checkbox" class="ace" name="custom_fields" value="1"
										<?php check_checked( (bool)$t_options['custom_fields'] ); ?>/>
									<span class="lbl">&#160;<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_cb_custom_fields' ) ); ?></span>
								</label>
								&#160;&#160;
								<label class="inline">
									<input type="checkbox" class="ace" name="bugnotes" value="1"
										<?php check_checked( (bool)$t_options['bugnotes'] ); ?>/>
									<span class="lbl">&#160;<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_cb_bugnotes' ) ); ?></span>
								</label>
							</td>
						</tr>

						<tr>
							<th class="category">
								<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_label_status' ) ); ?>
							</th>
							<td>
								<select name="status[]" multiple="multiple" size="5" class="input-sm">
									<?php foreach( $t_status_enum as $t_status_code => $t_status_label ) { ?>
										<option value="<?php echo (int)$t_status_code; ?>"
											<?php echo in_array( (int)$t_status_code, $f_statuses, true ) ? 'selected="selected"' : ''; ?>>
											<?php echo string_display_line( get_enum_element( 'status', $t_status_code ) ); ?>
										</option>
									<?php } ?>
								</select>
								<p class="help-block">
									<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_label_all_statuses' ) ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th class="category">
								<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_label_per_page' ) ); ?>
							</th>
							<td>
								<select name="per_page" class="input-sm">
									<?php foreach( $t_allowed_per_page as $t_pp ) { ?>
										<option value="<?php echo (int)$t_pp; ?>"
											<?php check_selected( (int)$f_per_page, (int)$t_pp ); ?>>
											<?php echo (int)$t_pp; ?>
										</option>
									<?php } ?>
								</select>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="widget-toolbox padding-8 clearfix">
				<button type="submit" class="btn btn-primary btn-sm btn-white btn-round">
					<i class="ace-icon fa fa-search"></i>
					<?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_btn_search' ) ); ?>
				</button>
			</div>
		</div>
	</div>
</form>

<div class="space-10"></div>

<?php
# --- Messages / results ------------------------------------------------------

if( $t_empty_query ) {
	echo '<div class="alert alert-warning">'
		. string_display_line( plugin_lang_get( 'plugin_fulltextsearch_error_empty_query' ) )
		. '</div>';
}

if( $t_error !== null ) {
	echo '<div class="alert alert-danger">' . string_display_line( $t_error ) . '</div>';
}

if( $t_results !== null && $t_error === null ) {
	$t_total = (int)$t_results['total'];

	if( $t_total == 0 ) {
		echo '<div class="alert alert-info">'
			. string_display_line( plugin_lang_get( 'plugin_fulltextsearch_results_none' ) )
			. '</div>';
	} else {
		# Count line: "X results for »term«".
		$t_count_text = sprintf(
			plugin_lang_get( 'plugin_fulltextsearch_results_count' ),
			$t_total,
			string_display_line( $t_query_trimmed )
		);
		echo '<h4>' . $t_count_text . '</h4>';

		# Filter set carried through pagination links.
		$t_filter_params = array(
			'page'          => $t_route,
			'fts_submit'    => 1,
			'q'             => $f_query,
			'project_id'    => (int)$f_project_id,
			'per_page'      => (int)$f_per_page,
			'summary'       => $t_options['summary'] ? 1 : 0,
			'description'   => $t_options['description'] ? 1 : 0,
			'custom_fields' => $t_options['custom_fields'] ? 1 : 0,
			'bugnotes'      => $t_options['bugnotes'] ? 1 : 0,
		);
		foreach( $f_statuses as $t_idx => $t_status_val ) {
			$t_filter_params['status[' . $t_idx . ']'] = (int)$t_status_val;
		}
		?>
		<div class="table-responsive">
		<table class="table table-bordered table-condensed table-hover width100">
			<thead>
				<tr>
					<th><?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_col_id' ) ); ?></th>
					<th><?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_col_project' ) ); ?></th>
					<th><?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_col_status' ) ); ?></th>
					<th><?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_col_summary' ) ); ?></th>
					<th><?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_col_reporter' ) ); ?></th>
					<th><?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_col_updated' ) ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$t_row_idx = 0;
				foreach( $t_results['rows'] as $t_row ) {
					$t_row_idx++;
					$t_row_class = ( $t_row_idx % 2 == 0 ) ? 'row-2' : 'row-1';

					# Status colour badge using MantisBT's per-status colour.
					$t_status_label = get_enum_element( 'status', $t_row['status'] );
					$t_status_color = get_status_color(
						$t_row['status'],
						auth_get_current_user_id(),
						$t_row['project_id']
					);

					# Highlight the matched terms inside the summary.
					$t_summary_html = fts_highlight(
						string_html_specialchars( $t_row['summary'] ),
						$t_results['terms']
					);
					?>
					<tr class="<?php echo $t_row_class; ?>">
						<td>
							<a href="<?php echo string_attribute( string_get_bug_view_url( $t_row['id'] ) ); ?>">
								<?php echo bug_format_id( $t_row['id'] ); ?>
							</a>
						</td>
						<td><?php echo string_display_line( $t_row['project_name'] ); ?></td>
						<td>
							<span class="label" style="background-color:<?php echo string_attribute( $t_status_color ); ?>;">
								<?php echo string_display_line( $t_status_label ); ?>
							</span>
						</td>
						<td>
							<?php echo $t_summary_html; ?>
							<?php if( !empty( $t_row['snippets'] ) ) { ?>
								<details>
									<summary><?php echo string_display_line( plugin_lang_get( 'plugin_fulltextsearch_match_context_label' ) ); ?></summary>
									<?php foreach( $t_row['snippets'] as $t_snippet ) {
										$t_field_label = plugin_lang_get( $t_snippet['field'] );
										if( isset( $t_snippet['label'] ) ) {
											$t_field_label .= ' (' . string_display_line( $t_snippet['label'] ) . ')';
										}
										?>
										<span class="fts-snippet">
											<span class="fts-snippet-label"><?php echo $t_field_label; ?>:</span>
											<?php echo $t_snippet['snippet']; ?>
										</span>
									<?php } ?>
								</details>
							<?php } ?>
						</td>
						<td><?php echo string_display_line( $t_row['reporter_name'] ); ?></td>
						<td><?php echo string_display_line( date( config_get( 'short_date_format' ), $t_row['last_updated'] ) ); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		</div>

		<?php
		# --- Pagination ------------------------------------------------------
		$t_page       = (int)$t_results['page'];
		$t_page_count = (int)$t_results['page_count'];

		if( $t_page_count > 1 ) {
			echo '<div class="btn-group">';

			if( $t_page > 1 ) {
				echo '<a class="btn btn-sm btn-white btn-primary" href="'
					. string_attribute( fts_page_url( $t_filter_params, $t_page - 1 ) ) . '">'
					. '&laquo;</a>';
			}

			echo '<span class="btn btn-sm btn-white disabled">'
				. sprintf( '%d / %d', $t_page, $t_page_count )
				. '</span>';

			if( $t_page < $t_page_count ) {
				echo '<a class="btn btn-sm btn-white btn-primary" href="'
					. string_attribute( fts_page_url( $t_filter_params, $t_page + 1 ) ) . '">'
					. '&raquo;</a>';
			}

			echo '</div>';
		}
	}
}
?>
</div>

<?php
layout_page_end();

/**
 * Build a pagination URL for a given page number, preserving all filters.
 *
 * @param array $p_filter_params Filter parameters already keyed for the query
 *                               string (including the routing "page" param).
 * @param int   $p_page_num      Target 1-based page number.
 * @return string Absolute MantisBT URL for the requested results page.
 */
function fts_page_url( array $p_filter_params, $p_page_num ) {
	$t_params = $p_filter_params;
	$t_params['page_num'] = (int)$p_page_num;
	return helper_mantis_url( 'plugin.php?' . http_build_query( $t_params ) );
}
