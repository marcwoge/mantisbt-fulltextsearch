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

		$this->author  = 'marcwoge';
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
