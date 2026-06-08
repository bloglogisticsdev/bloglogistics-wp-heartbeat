<?php
/**
 * Plugin Name:       BlogLogistics WP Heartbeat
 * Plugin URI:        https://github.com/bloglogisticsdev/bloglogistics-wp-heartbeat
 * Description:       Adjusts or disables the WordPress Heartbeat API in the dashboard, post editor, and frontend.
 * Version:           2.1.2
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            BlogLogistics
 * Author URI:        https://www.bloglogistics.com/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https://github.com/bloglogisticsdev/bloglogistics-wp-heartbeat
 * Text Domain:       bloglogistics-wp-heartbeat
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOGLOGISTICS_WPH_VERSION', '2.1.2' );
define( 'BLOGLOGISTICS_WPH_SLUG', 'bloglogistics-wp-heartbeat' );
define( 'BLOGLOGISTICS_WPH_FILE', __FILE__ );
define( 'BLOGLOGISTICS_WPH_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOGLOGISTICS_WPH_REPO_URL', 'https://github.com/bloglogisticsdev/bloglogistics-wp-heartbeat/' );
define( 'BLOGLOGISTICS_WPH_UPDATE_MANIFEST_URL', 'https://updates.bloglogistics.com/plugins/bloglogistics-wp-heartbeat.json' );

require_once BLOGLOGISTICS_WPH_DIR . 'includes/class-bloglogistics-wp-heartbeat.php';

$bloglogistics_wph_puc = BLOGLOGISTICS_WPH_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

if ( file_exists( $bloglogistics_wph_puc ) ) {
	if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory', false ) ) {
		require_once $bloglogistics_wph_puc;
	}

	require_once BLOGLOGISTICS_WPH_DIR . 'includes/class-bloglogistics-wp-heartbeat-updater.php';

	if ( class_exists( 'BlogLogistics_WP_Heartbeat_Updater', false ) ) {
		BlogLogistics_WP_Heartbeat_Updater::init(
			array(
				'repo_url'    => BLOGLOGISTICS_WPH_UPDATE_MANIFEST_URL,
				'plugin_file' => BLOGLOGISTICS_WPH_FILE,
				'slug'        => BLOGLOGISTICS_WPH_SLUG,
			)
		);
	}
}

register_activation_hook( __FILE__, array( 'BlogLogistics_WP_Heartbeat', 'activate' ) );

new BlogLogistics_WP_Heartbeat();
