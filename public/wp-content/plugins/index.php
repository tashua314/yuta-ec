<?php
/*
	Plugin Name: Yuta's pluglins
	Plugin URI: TODO
	Description: カスタムプラグイン
	Author: Yuta Takahashi
	Version: 1.0
	Author URI: TODO
*/

/**
 * ナビゲーションメニューの有無を確認
 *
 */
function wp_nav_menu_exist( $args = array() ) {
  $menu = wp_nav_menu($args);

  return trim($menu);
}
