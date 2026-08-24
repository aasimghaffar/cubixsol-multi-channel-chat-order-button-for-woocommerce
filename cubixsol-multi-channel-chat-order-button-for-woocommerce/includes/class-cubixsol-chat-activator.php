<?php
/**
 * Fired during plugin activation.
 * Creates the dedicated cubixsol_chat_abandoned_carts database table and default options.
 *
 * @package Cubixsol_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cubixsol_Chat_Activator {

	/**
	 * Bump when the table schema changes; triggers dbDelta on upgrade.
	 */
	const DB_VERSION = '1.1';

	/**
	 * Re-run dbDelta when the schema version stored in the DB is outdated.
	 * Hooked on admin_init so existing installs pick up new columns
	 * without requiring reactivation.
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'cubixsol_chat_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
			update_option( 'cubixsol_chat_db_version', self::DB_VERSION );
		}
	}

	/**
	 * Run activation tasks.
	 */
	public static function activate() {
		self::create_tables();
		self::set_default_options();
		update_option( 'cubixsol_chat_db_version', self::DB_VERSION );
	}

	/**
	 * Create the abandoned carts table.
	 */
	public static function create_tables() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'cubixsol_chat_abandoned_carts';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			cart_token varchar(64) NOT NULL,
			customer_name varchar(100) DEFAULT '',
			customer_email varchar(100) DEFAULT '',
			customer_phone varchar(50) DEFAULT '',
			cart_contents longtext,
			cart_total decimal(10,2) DEFAULT 0.00,
			cart_status varchar(20) DEFAULT 'abandoned',
			recovery_sent_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY cart_token (cart_token),
			KEY cart_status (cart_status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Single source of truth for the plugin's default settings.
	 * Used by the activator, the Settings API registration, and page rendering.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array(
			'enable_floating_widget' => 'yes',
			'default_phone'          => '',
			'widget_position'        => 'bottom-right',
			'widget_greeting'        => __( 'Hello! How can we help you today?', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'button_cta_text'        => __( 'Chat with us', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'button_icon'            => 'whatsapp',
			'widget_theme_color'     => '#25D366',
			'popup_header_title'     => __( 'Customer Support', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'popup_header_subtitle'  => __( 'Typically replies within a few minutes', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'sending_mode'           => 'direct',
			'meta_api_token'         => '',
			'meta_phone_number_id'   => '',
			'meta_template_name'     => '',
			'meta_template_lang'     => 'en_US',
			'enable_auto_recovery_send' => 'no',
			'recovery_send_delay'    => 60,
			'enable_ga4_tracking'    => 'no',
			'enable_fb_tracking'     => 'no',
			'enable_woo_order_btn'   => 'yes',
			'woo_btn_text'           => __( 'Order via WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'woo_btn_position'       => 'after_add_to_cart',
			'woo_btn_bg_color'       => '#25D366',
			'woo_message_template'   => "Hi! I would like to order this item:\n• Product: {product_name}\n• Price: {product_price} (SKU: {product_sku})\n• Link: {product_url}\nPlease confirm availability!",
			'order_notify_template'  => "Hello {customer_name}! Your order #{order_id} on {site_title} is now {order_status}.\nTotal: {order_total}\nThank you for shopping with us!",
			'enable_abandoned_cart'  => 'yes',
			'abandoned_cart_msg'     => "Hi {customer_name}! We noticed you left items in your cart on {site_title}. Use coupon code 'SAVE10' for 10% off your order today! Click here to resume: {cart_url}",
			'agents'                 => array(
				array(
					'name'        => 'Sarah Jenkins',
					'role'        => __( 'Sales & Orders', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
					'channel'     => 'whatsapp',
					'phone'       => '+15551234567',
					'hours_start' => '09:00',
					'hours_end'   => '18:00',
					'status'      => 'online',
				),
				array(
					'name'        => 'David Chen',
					'role'        => __( 'Technical Support', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
					'channel'     => 'whatsapp',
					'phone'       => '+15559876543',
					'hours_start' => '08:00',
					'hours_end'   => '20:00',
					'status'      => 'online',
				),
			),
		);
	}

	/**
	 * Store defaults on first activation only.
	 */
	public static function set_default_options() {
		if ( false === get_option( 'cubixsol_chat_settings', false ) ) {
			add_option( 'cubixsol_chat_settings', self::get_default_settings() );
		}
	}
}
