<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Cubixsol_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cubixsol_Chat_Deactivator {

	/**
	 * Clean up transients or scheduled events. Data is preserved;
	 * full removal happens in uninstall.php.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'cubixsol_chat_daily_maintenance' );
		wp_clear_scheduled_hook( 'cubixsol_chat_recovery_cron' );
	}
}
