<?php
/**
 * The core plugin class.
 *
 * @package Cubixsol_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cubixsol_Chat {

	/**
	 * @var Cubixsol_Chat_Loader
	 */
	protected $loader;

	/**
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * @var string
	 */
	protected $version;

	public function __construct() {
		$this->version     = CUBIXSOL_CHAT_VERSION;
		$this->plugin_name = 'cubixsol-multi-channel-chat-order-button-for-woocommerce';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_meta_api_hooks();
	}

	private function load_dependencies() {
		require_once CUBIXSOL_CHAT_PLUGIN_DIR . 'includes/class-cubixsol-chat-loader.php';
		require_once CUBIXSOL_CHAT_PLUGIN_DIR . 'includes/class-cubixsol-chat-activator.php';
		require_once CUBIXSOL_CHAT_PLUGIN_DIR . 'includes/class-cubixsol-chat-meta-api.php';
		require_once CUBIXSOL_CHAT_PLUGIN_DIR . 'admin/class-cubixsol-chat-admin.php';
		require_once CUBIXSOL_CHAT_PLUGIN_DIR . 'public/class-cubixsol-chat-public.php';

		$this->loader = new Cubixsol_Chat_Loader();
	}

	private function define_admin_hooks() {
		$plugin_admin = new Cubixsol_Chat_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_filter( 'plugin_action_links_' . CUBIXSOL_CHAT_BASENAME, $plugin_admin, 'add_action_links' );

		// WooCommerce Order List Action.
		$this->loader->add_action( 'woocommerce_admin_order_actions_end', $plugin_admin, 'add_order_whatsapp_action_btn' );

		// AJAX Handlers.
		$this->loader->add_action( 'wp_ajax_cubixsol_chat_mark_recovered', $plugin_admin, 'ajax_mark_recovered' );
		$this->loader->add_action( 'wp_ajax_cubixsol_chat_meta_send_test', $plugin_admin, 'ajax_meta_send_test' );
		$this->loader->add_action( 'wp_ajax_cubixsol_chat_send_recovery_now', $plugin_admin, 'ajax_send_recovery_now' );
	}

	private function define_public_hooks() {
		$plugin_public = new Cubixsol_Chat_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $plugin_public, 'maybe_set_cart_token_cookie' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'render_floating_widget' );

		// Real-Time Checkout Session Capturing AJAX.
		$this->loader->add_action( 'wp_ajax_cubixsol_chat_capture_checkout', $plugin_public, 'ajax_capture_checkout' );
		$this->loader->add_action( 'wp_ajax_nopriv_cubixsol_chat_capture_checkout', $plugin_public, 'ajax_capture_checkout' );
		$this->loader->add_action( 'woocommerce_checkout_order_processed', $plugin_public, 'on_order_processed', 10, 3 );

		// WooCommerce Product Page Button — both positions are registered and the
		// setting is evaluated at render time, so position changes apply immediately.
		$this->loader->add_action( 'woocommerce_before_add_to_cart_button', $plugin_public, 'render_woo_order_button_before' );
		$this->loader->add_action( 'woocommerce_after_add_to_cart_button', $plugin_public, 'render_woo_order_button_after' );

		// Shortcode.
		add_shortcode( 'cubixsol_chat_button', array( $plugin_public, 'shortcode_button' ) );
	}

	private function define_meta_api_hooks() {
		$meta_api = new Cubixsol_Chat_Meta_API();

		$this->loader->add_filter( 'cron_schedules', $meta_api, 'add_cron_interval' ); // phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval -- 15 min, documented.
		$this->loader->add_action( 'init', $meta_api, 'maybe_schedule_cron' );
		$this->loader->add_action( Cubixsol_Chat_Meta_API::CRON_HOOK, $meta_api, 'cron_process_abandoned_carts' );
		$this->loader->add_action( 'admin_init', 'Cubixsol_Chat_Activator', 'maybe_upgrade' );
	}

	/**
	 * Run the loader to execute all registered hooks.
	 */
	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
}
