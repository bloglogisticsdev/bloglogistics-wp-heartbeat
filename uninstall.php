<?php
/**
 * Uninstall cleanup for BlogLogistics WP Heartbeat.
 *
 * Removes plugin settings.
 *
 * @package BlogLogistics_WP_Heartbeat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bloglogistics_wph_options' );
