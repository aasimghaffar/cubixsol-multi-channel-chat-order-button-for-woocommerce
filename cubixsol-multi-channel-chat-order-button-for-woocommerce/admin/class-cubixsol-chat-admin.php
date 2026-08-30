<?php
/**
 * The admin-specific functionality of the plugin.
 * Admin menus, Settings API, WooCommerce order-row action, and recovery AJAX.
 *
 * @package    Cubixsol_Chat
 * @subpackage Cubixsol_Chat/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cubixsol_Chat_Admin {

	/**
	 * @var string
	 */
	private $plugin_name;

	/**
	 * @var string
	 */
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue admin styles only on this plugin's screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_styles( $hook ) {
		if ( false === strpos( $hook, 'cubixsol-chat' ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			CUBIXSOL_CHAT_PLUGIN_URL . 'admin/css/cubixsol-chat-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueue admin scripts only on this plugin's screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( false === strpos( $hook, 'cubixsol-chat' ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			CUBIXSOL_CHAT_PLUGIN_URL . 'admin/js/cubixsol-chat-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'cubixsolChatAdminData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cubixsol_chat_admin_nonce' ),
				'siteUrl' => home_url( '/' ),
				'i18n'    => array(
					'confirmRecovered' => __( 'Mark this cart as recovered?', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
					'confirmApiSend'   => __( 'Send the recovery message to this customer now via the Meta API?', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
					'sending'          => __( 'Sending…', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
					'sent'             => __( 'API ✓', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
					'error'            => __( 'Something went wrong. Please try again.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Inline SVG chat icon for the admin menu (base64 data URI).
	 * Note: "dashicons-whatsapp" does not exist in core dashicons.
	 *
	 * @return string
	 */
	private function get_menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#a7aaad" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Standard technique for custom admin menu SVG icons.
	}

	/**
	 * Register the main menu and submenus.
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'Chat & Order', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			__( 'Chat & Order', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'manage_options',
			'cubixsol-chat-settings',
			array( $this, 'display_settings_page' ),
			$this->get_menu_icon(),
			56
		);

		add_submenu_page(
			'cubixsol-chat-settings',
			__( 'Widget & Settings - Chat & Order', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			__( 'Widget & Settings', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'manage_options',
			'cubixsol-chat-settings',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'cubixsol-chat-settings',
			__( 'Cart Recovery Log & Analytics - Chat & Order', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			__( 'Cart Recovery Log', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'manage_options',
			'cubixsol-chat-recovery',
			array( $this, 'display_recovery_page' )
		);

		add_submenu_page(
			'cubixsol-chat-settings',
			__( 'Order Alerts Hub - Chat & Order', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			__( 'Order Alerts Hub', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'manage_options',
			'cubixsol-chat-orders',
			array( $this, 'display_orders_page' )
		);
	}

	/**
	 * Register the settings option via the Settings API.
	 */
	public function register_settings() {
		register_setting(
			'cubixsol_chat_options_group',
			'cubixsol_chat_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);
	}

	/**
	 * Defaults come from a single shared source.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return Cubixsol_Chat_Activator::get_default_settings();
	}

	/**
	 * Sanitize every setting on save.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return get_option( 'cubixsol_chat_settings', array() );
		}

		$input  = is_array( $input ) ? $input : array();
		$output = array();

		$output['enable_floating_widget'] = isset( $input['enable_floating_widget'] ) ? 'yes' : 'no';
		$output['default_phone']          = sanitize_text_field( $input['default_phone'] ?? '' );

		$position                  = sanitize_text_field( $input['widget_position'] ?? 'bottom-right' );
		$output['widget_position'] = in_array( $position, array( 'bottom-right', 'bottom-left' ), true ) ? $position : 'bottom-right';

		$output['widget_greeting']       = sanitize_text_field( $input['widget_greeting'] ?? '' );
		$output['button_cta_text']       = sanitize_text_field( $input['button_cta_text'] ?? '' );
		$output['button_icon']           = sanitize_text_field( $input['button_icon'] ?? 'whatsapp' );
		$output['widget_theme_color']    = sanitize_hex_color( $input['widget_theme_color'] ?? '#25D366' );
		$output['popup_header_title']    = sanitize_text_field( $input['popup_header_title'] ?? '' );
		$output['popup_header_subtitle'] = sanitize_text_field( $input['popup_header_subtitle'] ?? '' );

		$mode                   = sanitize_text_field( $input['sending_mode'] ?? 'direct' );
		$output['sending_mode'] = in_array( $mode, array( 'direct', 'meta_api' ), true ) ? $mode : 'direct';

		$output['meta_api_token']       = sanitize_text_field( $input['meta_api_token'] ?? '' );
		$output['meta_phone_number_id'] = sanitize_text_field( $input['meta_phone_number_id'] ?? '' );

		// Meta template names: lowercase letters, numbers, underscores only.
		$output['meta_template_name'] = preg_replace( '/[^a-z0-9_]/', '', strtolower( sanitize_text_field( $input['meta_template_name'] ?? '' ) ) );

		// Language codes like en, en_US, pt_BR.
		$lang                         = sanitize_text_field( $input['meta_template_lang'] ?? 'en_US' );
		$output['meta_template_lang'] = preg_match( '/^[a-z]{2}(_[A-Z]{2})?$/', $lang ) ? $lang : 'en_US';

		$output['enable_auto_recovery_send'] = isset( $input['enable_auto_recovery_send'] ) ? 'yes' : 'no';

		$delay                         = absint( $input['recovery_send_delay'] ?? 60 );
		$output['recovery_send_delay'] = min( 10080, max( 5, $delay ) );

		$output['enable_ga4_tracking'] = isset( $input['enable_ga4_tracking'] ) ? 'yes' : 'no';
		$output['enable_fb_tracking']  = isset( $input['enable_fb_tracking'] ) ? 'yes' : 'no';

		$output['enable_woo_order_btn'] = isset( $input['enable_woo_order_btn'] ) ? 'yes' : 'no';
		$output['woo_btn_text']         = sanitize_text_field( $input['woo_btn_text'] ?? __( 'Order via WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) );

		$btn_pos                    = sanitize_text_field( $input['woo_btn_position'] ?? 'after_add_to_cart' );
		$output['woo_btn_position'] = in_array( $btn_pos, array( 'before_add_to_cart', 'after_add_to_cart' ), true ) ? $btn_pos : 'after_add_to_cart';

		$output['woo_btn_bg_color']      = sanitize_hex_color( $input['woo_btn_bg_color'] ?? '#25D366' );
		$output['woo_message_template']  = sanitize_textarea_field( $input['woo_message_template'] ?? '' );
		$output['order_notify_template'] = sanitize_textarea_field( $input['order_notify_template'] ?? '' );

		$output['enable_abandoned_cart'] = isset( $input['enable_abandoned_cart'] ) ? 'yes' : 'no';
		$output['abandoned_cart_msg']    = sanitize_textarea_field( $input['abandoned_cart_msg'] ?? '' );

		$output['agents'] = array();
		if ( isset( $input['agents'] ) && is_array( $input['agents'] ) ) {
			foreach ( $input['agents'] as $agent ) {
				if ( empty( $agent['name'] ) || empty( $agent['phone'] ) ) {
					continue;
				}

				$channel = sanitize_text_field( $agent['channel'] ?? 'whatsapp' );
				$status  = sanitize_text_field( $agent['status'] ?? 'online' );

				$output['agents'][] = array(
					'name'        => sanitize_text_field( $agent['name'] ),
					'role'        => sanitize_text_field( $agent['role'] ?? '' ),
					'channel'     => in_array( $channel, array( 'whatsapp', 'telegram', 'phone' ), true ) ? $channel : 'whatsapp',
					'phone'       => sanitize_text_field( $agent['phone'] ),
					'hours_start' => sanitize_text_field( $agent['hours_start'] ?? '09:00' ),
					'hours_end'   => sanitize_text_field( $agent['hours_end'] ?? '18:00' ),
					'status'      => in_array( $status, array( 'online', 'offline' ), true ) ? $status : 'online',
				);
			}
		}

		return $output;
	}

	/**
	 * Plugin list action links.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=cubixsol-chat-settings' ) ) . '">' . esc_html__( 'Settings', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) . '</a>';
		$recovery_link = '<a href="' . esc_url( admin_url( 'admin.php?page=cubixsol-chat-recovery' ) ) . '" style="color:#16a34a;font-weight:bold;">' . esc_html__( 'Cart Recovery Log', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) . '</a>';
		array_unshift( $links, $settings_link, $recovery_link );
		return $links;
	}

	/**
	 * WhatsApp quick-action button on the WooCommerce Orders list.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function add_order_whatsapp_action_btn( $order ) {
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$billing_phone = $order->get_billing_phone();
		if ( empty( $billing_phone ) ) {
			return;
		}

		$options  = wp_parse_args( get_option( 'cubixsol_chat_settings', array() ), $this->get_default_settings() );
		$template = $options['order_notify_template'] ?? '';

		$msg = str_replace(
			array( '{customer_name}', '{order_id}', '{site_title}', '{order_status}', '{order_total}' ),
			array(
				$order->get_billing_first_name(),
				$order->get_id(),
				get_bloginfo( 'name' ),
				wc_get_order_status_name( $order->get_status() ),
				html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ) ),
			),
			$template
		);

		$clean_phone = preg_replace( '/[^0-9]/', '', $billing_phone );
		$wa_url      = 'https://wa.me/' . $clean_phone . '?text=' . rawurlencode( $msg );

		printf(
			'<a class="button wc-action-button wc-action-button-whatsapp cubixsol-chat-order-action-btn" href="%1$s" target="_blank" rel="noopener noreferrer" title="%2$s">%3$s</a>',
			esc_url( $wa_url ),
			esc_attr__( 'Send WhatsApp Order Notification', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'<span class="dashicons dashicons-format-chat" style="vertical-align:middle;font-size:16px;"></span>'
		);
	}

	/**
	 * AJAX: mark an abandoned cart row as recovered (actually updates the DB).
	 */
	public function ajax_mark_recovered() {
		check_ajax_referer( 'cubixsol_chat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		$cart_id = isset( $_POST['cart_id'] ) ? absint( wp_unslash( $_POST['cart_id'] ) ) : 0;
		if ( ! $cart_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid cart ID.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'cubixsol_chat_abandoned_carts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table.
		$updated = $wpdb->update(
			$table_name,
			array(
				'cart_status' => 'recovered',
				'updated_at'  => current_time( 'mysql' ),
			),
			array( 'id' => $cart_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			wp_send_json_error( array( 'message' => __( 'Database update failed.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Cart marked as recovered!', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
	}

	/**
	 * AJAX: send a test message through the Meta Cloud API to verify credentials.
	 */
	public function ajax_meta_send_test() {
		check_ajax_referer( 'cubixsol_chat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		if ( strlen( Cubixsol_Chat_Meta_API::normalize_phone( $phone ) ) < 7 ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid phone number in international format.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		$api = new Cubixsol_Chat_Meta_API();

		if ( ! $api->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Select "Meta WhatsApp Cloud API" mode and save your token and Phone Number ID first.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		$options  = wp_parse_args( get_option( 'cubixsol_chat_settings', array() ), Cubixsol_Chat_Activator::get_default_settings() );
		$template = $options['meta_template_name'];

		// A configured template is tried first; otherwise Meta's built-in
		// hello_world template, which every test number has pre-approved.
		if ( '' !== $template ) {
			$result = $api->send_template( $phone, $template, $options['meta_template_lang'], array( __( 'there', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ), home_url( '/' ) ) );
		} else {
			$result = $api->send_template( $phone, 'hello_world', 'en_US' );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %s: WhatsApp message ID returned by Meta. */
					__( 'Message accepted by Meta (ID: %s). Check the recipient\'s WhatsApp.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
					$result['messages'][0]['id']
				),
			)
		);
	}

	/**
	 * AJAX: send the recovery message for one abandoned cart immediately.
	 */
	public function ajax_send_recovery_now() {
		check_ajax_referer( 'cubixsol_chat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		$cart_id = isset( $_POST['cart_id'] ) ? absint( wp_unslash( $_POST['cart_id'] ) ) : 0;
		if ( ! $cart_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid cart ID.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table.
		$cart = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cubixsol_chat_abandoned_carts WHERE id = %d", $cart_id ) );

		if ( ! $cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart not found.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		$api = new Cubixsol_Chat_Meta_API();

		if ( ! $api->is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Meta Cloud API mode is not configured.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
		}

		$result = $api->send_recovery_for_cart( $cart );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$api->mark_sent( $cart_id );

		wp_send_json_success( array( 'message' => __( 'Recovery message sent via Meta API.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) );
	}

	/**
	 * Render: Widget & Settings page.
	 */
	public function display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = wp_parse_args( get_option( 'cubixsol_chat_settings', array() ), $this->get_default_settings() );
		require CUBIXSOL_CHAT_PLUGIN_DIR . 'admin/partials/cubixsol-chat-admin-settings.php';
	}

	/**
	 * Render: Cart Recovery page.
	 */
	public function display_recovery_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = wp_parse_args( get_option( 'cubixsol_chat_settings', array() ), $this->get_default_settings() );
		require CUBIXSOL_CHAT_PLUGIN_DIR . 'admin/partials/cubixsol-chat-admin-recovery.php';
	}

	/**
	 * Render: Order Alerts page.
	 */
	public function display_orders_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = wp_parse_args( get_option( 'cubixsol_chat_settings', array() ), $this->get_default_settings() );
		require CUBIXSOL_CHAT_PLUGIN_DIR . 'admin/partials/cubixsol-chat-admin-orders.php';
	}
}
