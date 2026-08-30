<?php
/**
 * The plugin bootstrap file.
 *
 * @link              https://cubixsol.com/
 * @since             1.0.0
 * @package           Cubixsol_Chat
 *
 * @wordpress-plugin
 * Plugin Name:       Cubixsol Multi-Channel Chat & Order Button for WooCommerce
 * Plugin URI:        https://cubixsol.com/products/
 * Description:       Floating WhatsApp & multi-channel chat widget, multi-agent support, automated cart recovery, order alerts, and 1-click 'Order via WhatsApp' button for WooCommerce.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Cubixsol
 * Author URI:        https://cubixsol.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cubixsol-multi-channel-chat-order-button-for-woocommerce
 * Domain Path:       /languages
 *
 * WC requires at least: 5.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CUBIXSOL_CHAT_VERSION', '1.0.0' );
define( 'CUBIXSOL_CHAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CUBIXSOL_CHAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CUBIXSOL_CHAT_BASENAME', plugin_basename( __FILE__ ) );
define( 'CUBIXSOL_CHAT_PLUGIN_FILE', __FILE__ );

/**
 * Check whether WooCommerce is active.
 *
 * @return bool
 */
function cubixsol_chat_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * The code that runs during plugin activation.
 * Blocks activation when WooCommerce is missing (fallback for WP < 6.5,
 * where the "Requires Plugins" header is not enforced).
 */
function cubixsol_chat_activate() {
	if ( ! cubixsol_chat_is_woocommerce_active() ) {
		deactivate_plugins( CUBIXSOL_CHAT_BASENAME );
		wp_die(
			esc_html__( 'Chat & Order requires WooCommerce to be installed and active. Please install and activate WooCommerce first.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			esc_html__( 'Plugin dependency check', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			array(
				'back_link' => true,
			)
		);
	}

	require_once CUBIXSOL_CHAT_PLUGIN_DIR . 'includes/class-cubixsol-chat-activator.php';
	Cubixsol_Chat_Activator::activate();
}
register_activation_hook( __FILE__, 'cubixsol_chat_activate' );

/**
 * The code that runs during plugin deactivation.
 */
function cubixsol_chat_deactivate() {
	require_once CUBIXSOL_CHAT_PLUGIN_DIR . 'includes/class-cubixsol-chat-deactivator.php';
	Cubixsol_Chat_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'cubixsol_chat_deactivate' );

/**
 * Admin notice shown when WooCommerce is deactivated while Cubixsol Chat is active.
 */
function cubixsol_chat_woocommerce_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
		esc_html__( 'Chat & Order:', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
		esc_html__( 'WooCommerce is required for this plugin to work. Please install and activate WooCommerce.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' )
	);
}

/**
 * Admin notice shown right after Cubixsol Chat deactivates itself.
 */
function cubixsol_chat_self_deactivated_notice() {
	printf(
		'<div class="notice notice-error is-dismissible"><p><strong>%1$s</strong> %2$s</p></div>',
		esc_html__( 'Chat & Order has been deactivated.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
		esc_html__( 'It requires WooCommerce to be installed and active. Activate WooCommerce, then reactivate Chat & Order.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' )
	);
}

/**
 * Self-deactivate when WooCommerce is missing or gets deactivated.
 * Runs on admin_init so plugin.php functions are available.
 */
function cubixsol_chat_maybe_self_deactivate() {
	if ( cubixsol_chat_is_woocommerce_active() ) {
		return;
	}
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	deactivate_plugins( CUBIXSOL_CHAT_BASENAME );
	add_action( 'admin_notices', 'cubixsol_chat_self_deactivated_notice' );

	// Suppress the default "Plugin activated." message, which would be misleading.
	if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI state cleanup, no data processed.
		unset( $_GET['activate'] );
	}
}
add_action( 'admin_init', 'cubixsol_chat_maybe_self_deactivate' );

/**
 * Declare compatibility with WooCommerce HPOS (custom order tables).
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CUBIXSOL_CHAT_PLUGIN_FILE, true );
		}
	}
);

/**
 * Begins execution of the plugin, only when WooCommerce is active.
 */
function cubixsol_chat_run() {
	if ( ! cubixsol_chat_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'cubixsol_chat_woocommerce_missing_notice' );
		return;
	}

	require_once CUBIXSOL_CHAT_PLUGIN_DIR . 'includes/class-cubixsol-chat.php';

	$plugin = new Cubixsol_Chat();
	$plugin->run();
}
add_action( 'plugins_loaded', 'cubixsol_chat_run', 20 );
