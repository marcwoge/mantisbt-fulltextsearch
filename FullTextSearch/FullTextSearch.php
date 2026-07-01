<?php
# FullTextSearch - Full-text search plugin for MantisBT
# Copyright (c) 2026 Marc-Philipp Woge
#
# Licensed under the MIT License. See the LICENSE file in the project root
# for the full license text.
#
# SPDX-License-Identifier: MIT

/**
 * FullTextSearch plugin main class.
 *
 * Adds a genuine full-text search across all issue fields (summary,
 * description, steps to reproduce, additional information, custom field
 * values and bugnotes) including a dedicated search page reachable from the
 * main navigation bar.
 *
 * The plugin only reads existing MantisBT tables; it does not create or
 * modify any schema.
 */
class FullTextSearchPlugin extends MantisPlugin {

	/**
	 * Register plugin metadata.
	 *
	 * @return void
	 */
	function register() {
		$this->name        = plugin_lang_get( 'title' );
		$this->description  = plugin_lang_get( 'description' );
		$this->page         = '';

		$this->version  = '1.0.0';
		$this->requires = array(
			'MantisCore' => '2.25.0',
		);

		$this->author  = 'Marc-Philipp Woge';
		$this->contact  = 'marc.woge@googlemail.com';
		$this->url      = 'https://github.com/marcwoge/mantisbt-fulltextsearch';
	}

	/**
	 * Map plugin event hooks to handler methods.
	 *
	 * @return array
	 */
	function hooks() {
		return array(
			'EVENT_MENU_MAIN' => 'on_menu_main',
		);
	}

	/**
	 * Build the main navigation menu entry.
	 *
	 * MantisBT 2.x renders EVENT_MENU_MAIN contributions in the sidebar via
	 * layout_options_for_sidebar(), which expects each item to be an
	 * associative array with 'url', 'title' and (optionally) 'icon' keys -
	 * raw HTML anchor strings (the MantisBT 1.x convention) are ignored. The
	 * 'title' is passed through lang_get_defaulted(), so the already-translated
	 * label is rendered verbatim when it is not itself a language key.
	 *
	 * @return array
	 */
	function on_menu_main() {
		return array(
			array(
				'title' => plugin_lang_get( 'plugin_fulltextsearch_menu_label' ),
				'url'   => plugin_page( 'search' ),
				'icon'  => 'fa-search',
			),
		);
	}
}
