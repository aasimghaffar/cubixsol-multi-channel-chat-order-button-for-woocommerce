<?php
/**
 * The public-facing functionality of the plugin.
 * Floating widget, 1-click WooCommerce button, and Real-Time Checkout Session Capturing.
 *
 * @package    Cubixsol_Chat
 * @subpackage Cubixsol_Chat/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cubixsol_Chat_Public {

	/**
	 * @var string
	 */
	private $plugin_name;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var string
	 */
	private $session_token = '';

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->options     = wp_parse_args( get_option( 'cubixsol_chat_settings', array() ), Cubixsol_Chat_Activator::get_default_settings() );
	}

	/**
	 * Set the cart-token cookie on init (before headers are sent),
	 * instead of inside wp_enqueue_scripts where headers may already be out.
	 */
	public function maybe_set_cart_token_cookie() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! empty( $_COOKIE['cubixsol_chat_cart_token'] ) ) {
			$this->session_token = sanitize_text_field( wp_unslash( $_COOKIE['cubixsol_chat_cart_token'] ) );
			return;
		}

		$this->session_token = md5( uniqid( 'cubixsol_chat_', true ) . wp_rand() );

		if ( ! headers_sent() ) {
			setcookie(
				'cubixsol_chat_cart_token',
				$this->session_token,
				time() + ( 30 * DAY_IN_SECONDS ),
				COOKIEPATH ? COOKIEPATH : '/',
				COOKIE_DOMAIN,
				is_ssl(),
				true
			);
		}
	}

	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			CUBIXSOL_CHAT_PLUGIN_URL . 'public/css/cubixsol-chat-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			CUBIXSOL_CHAT_PLUGIN_URL . 'public/js/cubixsol-chat-public.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'cubixsolChatVars',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'cubixsol_chat_public_nonce' ),
				'ga4Enabled'      => 'yes' === ( $this->options['enable_ga4_tracking'] ?? 'yes' ),
				'fbEnabled'       => 'yes' === ( $this->options['enable_fb_tracking'] ?? 'yes' ),
				'captureEnabled'  => 'yes' === ( $this->options['enable_abandoned_cart'] ?? 'yes' ),
				'cartToken'       => $this->session_token,
			)
		);
	}

	/**
	 * Real-time AJAX Checkout Capturer.
	 * Nonce is REQUIRED — silently ignoring a failed nonce is not acceptable
	 * for WordPress.org review and opens the endpoint to abuse.
	 */
	public function ajax_capture_checkout() {
		check_ajax_referer( 'cubixsol_chat_public_nonce', 'nonce' );

		if ( 'yes' !== ( $this->options['enable_abandoned_cart'] ?? 'yes' ) ) {
			wp_send_json_error( array( 'message' => 'Cart capturing is disabled.' ) );
		}

		$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$cart_token = isset( $_POST['cart_token'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_token'] ) ) : '';
		$items_raw  = isset( $_POST['client_items'] ) ? sanitize_textarea_field( wp_unslash( $_POST['client_items'] ) ) : '';

		if ( empty( $phone ) && empty( $email ) ) {
			wp_send_json_error( array( 'message' => 'No phone or email provided' ) );
		}

		// Ensure WooCommerce Cart is loaded in the admin-ajax context.
		if ( function_exists( 'WC' ) ) {
			if ( null === WC()->session ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deliberately invoking WooCommerce's own filter so custom session handlers are respected.
				$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
				WC()->session  = new $session_class();
				WC()->session->init();
			}
			if ( null === WC()->customer ) {
				WC()->customer = new WC_Customer( get_current_user_id(), true );
			}
			if ( null === WC()->cart ) {
				WC()->cart = new WC_Cart();
				WC()->cart->get_cart_from_session();
			}
		}

		$cart_items = array();
		$cart_total = 0.00;

		if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
			$cart_total = floatval( WC()->cart->get_total( 'edit' ) );
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$_product = $values['data'] ?? null;
				if ( $_product ) {
					$terms    = get_the_terms( $_product->get_id(), 'product_cat' );
					$cat_name = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : __( 'General', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );

					$cart_items[] = array(
						'product_id' => $_product->get_id(),
						'name'       => $_product->get_name(),
						'quantity'   => $values['quantity'],
						'price'      => $_product->get_price(),
						'category'   => $cat_name,
						'permalink'  => get_permalink( $_product->get_id() ),
					);
				}
			}
		}

		// Fallback: items scraped client-side (Blocks checkout / cached pages).
		if ( empty( $cart_items ) && ! empty( $items_raw ) ) {
			$decoded = json_decode( $items_raw, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$cart_items[] = array(
						'name'     => sanitize_text_field( $item['name'] ?? '' ),
						'quantity' => absint( $item['quantity'] ?? 1 ),
						'price'    => sanitize_text_field( $item['price'] ?? '' ),
						'category' => sanitize_text_field( $item['category'] ?? 'Store Products' ),
					);
				}
			}
		}

		if ( empty( $cart_items ) ) {
			$cart_items[] = array(
				'name'     => __( 'Store Product', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
				'quantity' => 1,
				'price'    => 0,
				'category' => __( 'Store Products', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			);
		}

		if ( empty( $cart_token ) ) {
			$cart_token = md5( $email . $phone . wp_rand() );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'cubixsol_chat_abandoned_carts';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, live data required.
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}cubixsol_chat_abandoned_carts WHERE cart_token = %s", $cart_token ) );

		if ( ! $existing && ! empty( $phone ) ) {
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}cubixsol_chat_abandoned_carts WHERE customer_phone = %s AND cart_status = 'abandoned'", $phone ) );
		}

		if ( $existing ) {
			$wpdb->update(
				$table_name,
				array(
					'customer_name'  => $name,
					'customer_email' => $email,
					'customer_phone' => $phone,
					'cart_contents'  => wp_json_encode( $cart_items ),
					'cart_total'     => $cart_total,
					'cart_status'    => 'abandoned',
					'updated_at'     => current_time( 'mysql' ),
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%s', '%s', '%f', '%s', '%s' ),
				array( '%d' )
			);
			$record_id = (int) $existing->id;
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'cart_token'     => $cart_token,
					'customer_name'  => $name,
					'customer_email' => $email,
					'customer_phone' => $phone,
					'cart_contents'  => wp_json_encode( $cart_items ),
					'cart_total'     => $cart_total,
					'cart_status'    => 'abandoned',
					'created_at'     => current_time( 'mysql' ),
					'updated_at'     => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
			);
			$record_id = (int) $wpdb->insert_id;
		}
		// phpcs:enable

		wp_send_json_success(
			array(
				'message' => 'Cart captured successfully',
				'id'      => $record_id,
			)
		);
	}

	/**
	 * Mark captured carts as recovered when the order completes checkout.
	 *
	 * @param int      $order_id    Order ID.
	 * @param array    $posted_data Posted checkout data.
	 * @param WC_Order $order       Order object.
	 */
	public function on_order_processed( $order_id, $posted_data, $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		$billing_phone = $order->get_billing_phone();
		$billing_email = $order->get_billing_email();

		global $wpdb;
		$table_name = $wpdb->prefix . 'cubixsol_chat_abandoned_carts';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table.
		if ( ! empty( $billing_phone ) ) {
			$wpdb->update(
				$table_name,
				array(
					'cart_status' => 'recovered',
					'updated_at'  => current_time( 'mysql' ),
				),
				array( 'customer_phone' => $billing_phone ),
				array( '%s', '%s' ),
				array( '%s' )
			);
		} elseif ( ! empty( $billing_email ) ) {
			$wpdb->update(
				$table_name,
				array(
					'cart_status' => 'recovered',
					'updated_at'  => current_time( 'mysql' ),
				),
				array( 'customer_email' => $billing_email ),
				array( '%s', '%s' ),
				array( '%s' )
			);
		}
		// phpcs:enable
	}

	/**
	 * Whether an agent is currently within working hours.
	 *
	 * @param array $agent Agent config.
	 * @return bool
	 */
	public function is_agent_online( $agent ) {
		if ( isset( $agent['status'] ) && 'offline' === $agent['status'] ) {
			return false;
		}
		if ( empty( $agent['hours_start'] ) || empty( $agent['hours_end'] ) ) {
			return true;
		}
		$current_time = current_time( 'H:i' );
		return ( $current_time >= $agent['hours_start'] && $current_time <= $agent['hours_end'] );
	}

	/**
	 * Render the floating multi-agent widget in the footer.
	 */
	public function render_floating_widget() {
		if ( 'yes' !== ( $this->options['enable_floating_widget'] ?? 'yes' ) ) {
			return;
		}

		$position      = $this->options['widget_position'] ?? 'bottom-right';
		$greeting      = $this->options['widget_greeting'] ?? __( 'Hello! How can we help you today?', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
		$cta_text      = $this->options['button_cta_text'] ?? __( 'Chat with us', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
		$header_title  = $this->options['popup_header_title'] ?? __( 'Customer Support', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
		$header_sub    = $this->options['popup_header_subtitle'] ?? __( 'Typically replies within a few minutes', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
		$agents        = $this->options['agents'] ?? array();
		$default_phone = $this->options['default_phone'] ?? '';
		$theme_color   = $this->options['widget_theme_color'] ?? '#25D366';

		require CUBIXSOL_CHAT_PLUGIN_DIR . 'public/partials/cubixsol-chat-public-widget.php';
	}

	/**
	 * Hook wrapper: render button before Add to Cart when configured.
	 */
	public function render_woo_order_button_before() {
		if ( 'before_add_to_cart' === ( $this->options['woo_btn_position'] ?? 'after_add_to_cart' ) ) {
			$this->render_woo_order_button();
		}
	}

	/**
	 * Hook wrapper: render button after Add to Cart when configured.
	 */
	public function render_woo_order_button_after() {
		if ( 'after_add_to_cart' === ( $this->options['woo_btn_position'] ?? 'after_add_to_cart' ) ) {
			$this->render_woo_order_button();
		}
	}

	/**
	 * Render the 1-click "Order via WhatsApp" button on single product pages.
	 */
	public function render_woo_order_button() {
		if ( 'yes' !== ( $this->options['enable_woo_order_btn'] ?? 'yes' ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$phone = $this->options['default_phone'] ?? '';
		if ( empty( $phone ) && ! empty( $this->options['agents'] ) ) {
			$phone = $this->options['agents'][0]['phone'] ?? '';
		}
		if ( empty( $phone ) ) {
			return;
		}

		$clean_phone = preg_replace( '/[^0-9]/', '', $phone );
		$template    = $this->options['woo_message_template'] ?? "Hi! I want to order {product_name} - {product_price}";
		$btn_text    = $this->options['woo_btn_text'] ?? __( 'Order via WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
		$btn_color   = $this->options['woo_btn_bg_color'] ?? '#25D366';

		$msg = str_replace(
			array( '{product_name}', '{product_price}', '{product_sku}', '{product_url}' ),
			array(
				$product->get_name(),
				html_entity_decode( wp_strip_all_tags( wc_price( $product->get_price() ) ) ),
				$product->get_sku() ? $product->get_sku() : 'N/A',
				get_permalink( $product->get_id() ),
			),
			$template
		);

		$wa_url = 'https://wa.me/' . $clean_phone . '?text=' . rawurlencode( $msg );
		?>
		<div class="cubixsol-chat-woo-button-wrap">
			<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="button cubixsol-chat-woo-order-btn cubixsol-chat-track-click" style="background-color:<?php echo esc_attr( $btn_color ); ?> !important;border-color:<?php echo esc_attr( $btn_color ); ?> !important;" data-product="<?php echo esc_attr( $product->get_name() ); ?>">
				<svg class="cubixsol-chat-woo-btn-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
				<span><?php echo esc_html( $btn_text ); ?></span>
			</a>
		</div>
		<?php
	}

	/**
	 * [cubixsol_chat_button] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_button( $atts ) {
		$args = shortcode_atts(
			array(
				'phone' => $this->options['default_phone'] ?? '',
				'text'  => __( 'Chat on WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
				'msg'   => __( 'Hi! I have an inquiry.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			),
			$atts,
			'cubixsol_chat_button'
		);

		$clean_phone = preg_replace( '/[^0-9]/', '', $args['phone'] );
		if ( empty( $clean_phone ) ) {
			return '';
		}

		$wa_url = 'https://wa.me/' . $clean_phone . '?text=' . rawurlencode( $args['msg'] );

		return '<a href="' . esc_url( $wa_url ) . '" target="_blank" rel="noopener noreferrer" class="cubixsol-chat-shortcode-btn cubixsol-chat-track-click">' . esc_html( $args['text'] ) . '</a>';
	}
}
