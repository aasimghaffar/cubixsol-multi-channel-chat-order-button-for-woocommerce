<?php
/**
 * Fired when the plugin is uninstalled (deleted).
 * Removes all plugin data: options and the abandoned carts table.
 *
 * @package Cubixsol_Chat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'cubixsol_chat_settings' );
delete_option( 'cubixsol_chat_db_version' );

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- One-time cleanup on uninstall; caching is not applicable to DROP TABLE.
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'cubixsol_chat_abandoned_carts' ) );
