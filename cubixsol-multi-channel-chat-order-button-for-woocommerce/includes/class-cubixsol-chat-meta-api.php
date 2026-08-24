<?php
/**
 * Meta WhatsApp Cloud API client.
 *
 * Sends messages through graph.facebook.com when the store owner has
 * selected "Meta WhatsApp Cloud API" mode and entered their credentials.
 * No request is ever made unless the owner explicitly configures this mode.
 *
 * @package Cubixsol_Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cubixsol_Chat_Meta_API {

	/**
	 * Graph API version used for all requests.
	 */
	const API_VERSION = 'v20.0';

	/**
	 * Cron hook name for automated cart recovery sending.
	 */
	const CRON_HOOK = 'cubixsol_chat_recovery_cron';

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $options;

	public function __construct() {
		$defaults      = Cubixsol_Chat_Activator::get_default_settings();
		$this->options = wp_parse_args( get_option( 'cubixsol_chat_settings', array() ), $defaults );
	}

	/**
	 * Whether Meta API mode is selected and credentials are present.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return 'meta_api' === ( $this->options['sending_mode'] ?? 'direct' )
			&& ! empty( $this->options['meta_api_token'] )
			&& ! empty( $this->options['meta_phone_number_id'] );
	}

	/**
	 * Normalize a phone number to Cloud API format: digits only, international.
	 *
	 * @param string $phone Raw phone number.
	 * @return string Digits-only phone number.
	 */
	public static function normalize_phone( $phone ) {
		return preg_replace( '/[^0-9]/', '', (string) $phone );
	}

	/**
	 * Send a plain text message.
	 *
	 * Note: Meta only delivers free-form text within the 24-hour customer
	 * service window (i.e. the recipient messaged the business first).
	 * Outside that window, use send_template().
	 *
	 * @param string $to   Recipient phone number (international format).
	 * @param string $body Message text.
	 * @return array|WP_Error Decoded API response or WP_Error.
	 */
	public function send_text( $to, $body ) {
		return $this->request(
			array(
				'messaging_product' => 'whatsapp',
				'to'                => self::normalize_phone( $to ),
				'type'              => 'text',
				'text'              => array(
					'preview_url' => true,
					'body'        => $body,
				),
			)
		);
	}

	/**
	 * Send a pre-approved template message.
	 *
	 * @param string $to          Recipient phone number.
	 * @param string $template    Template name (as approved in Meta Business Manager).
	 * @param string $lang        Template language code, e.g. en_US.
	 * @param array  $body_params Ordered values for the template body variables {{1}}, {{2}}, ...
	 * @return array|WP_Error Decoded API response or WP_Error.
	 */
	public function send_template( $to, $template, $lang = 'en_US', $body_params = array() ) {
		$payload = array(
			'messaging_product' => 'whatsapp',
			'to'                => self::normalize_phone( $to ),
			'type'              => 'template',
			'template'          => array(
				'name'     => $template,
				'language' => array( 'code' => $lang ),
			),
		);

		if ( ! empty( $body_params ) ) {
			$parameters = array();
			foreach ( $body_params as $value ) {
				$parameters[] = array(
					'type' => 'text',
					'text' => (string) $value,
				);
			}
			$payload['template']['components'] = array(
				array(
					'type'       => 'body',
					'parameters' => $parameters,
				),
			);
		}

		return $this->request( $payload );
	}

	/**
	 * Perform the HTTP request against the Cloud API.
	 *
	 * @param array $payload Message payload.
	 * @return array|WP_Error Decoded response array on success, WP_Error on failure.
	 */
	private function request( $payload ) {
		if ( empty( $this->options['meta_api_token'] ) || empty( $this->options['meta_phone_number_id'] ) ) {
			return new WP_Error( 'cubixsol_chat_not_configured', __( 'Meta API token or Phone Number ID is missing.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) );
		}

		$url = sprintf(
			'https://graph.facebook.com/%s/%s/messages',
			self::API_VERSION,
			rawurlencode( $this->options['meta_phone_number_id'] )
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->options['meta_api_token'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $data['messages'][0]['id'] ) ) {
			return $data;
		}

		$api_message = $data['error']['message'] ?? __( 'Unknown Meta API error.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
		$api_code    = $data['error']['code'] ?? $code;

		return new WP_Error(
			'cubixsol_chat_meta_api_error',
			sprintf(
				/* translators: 1: Meta error code, 2: Meta error message. */
				__( 'Meta API error %1$s: %2$s', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
				$api_code,
				$api_message
			)
		);
	}

	/**
	 * Build and send the recovery message for one abandoned cart row.
	 *
	 * Uses the configured template (recommended — deliverable at any time) with
	 * {{1}} = customer name and {{2}} = cart link. Falls back to a plain text
	 * message built from the "Abandoned cart message" setting when no template
	 * name is configured (deliverable only inside the 24-hour window).
	 *
	 * @param object $cart Row from the cubixsol_chat_abandoned_carts table.
	 * @return array|WP_Error
	 */
	public function send_recovery_for_cart( $cart ) {
		if ( empty( $cart->customer_phone ) ) {
			return new WP_Error( 'cubixsol_chat_no_phone', __( 'This cart has no phone number.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) );
		}

		$customer_name = '' !== trim( (string) $cart->customer_name )
			? $cart->customer_name
			: __( 'there', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );

		$cart_url = function_exists( 'wc_get_cart_url' )
			? add_query_arg( 'coupon', 'SAVE10', wc_get_cart_url() )
			: home_url( '/cart/' );

		$template = $this->options['meta_template_name'] ?? '';

		if ( '' !== $template ) {
			return $this->send_template(
				$cart->customer_phone,
				$template,
				$this->options['meta_template_lang'] ?? 'en_US',
				array( $customer_name, $cart_url )
			);
		}

		$body = strtr(
			(string) ( $this->options['abandoned_cart_msg'] ?? '' ),
			array(
				'{customer_name}' => $customer_name,
				'{site_title}'    => get_bloginfo( 'name' ),
				'{cart_url}'      => $cart_url,
				'{cart_total}'    => function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $cart->cart_total ) ) : $cart->cart_total,
			)
		);

		return $this->send_text( $cart->customer_phone, $body );
	}

	/**
	 * Mark a cart row as sent.
	 *
	 * @param int $cart_id Row ID.
	 */
	public function mark_sent( $cart_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table.
		$wpdb->update(
			$wpdb->prefix . 'cubixsol_chat_abandoned_carts',
			array( 'recovery_sent_at' => current_time( 'mysql' ) ),
			array( 'id' => absint( $cart_id ) ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Register the custom cron interval.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_cron_interval( $schedules ) {
		$schedules['cubixsol_chat_15min'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 15 minutes (Cubixsol Chat)', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
		);
		return $schedules;
	}

	/**
	 * Schedule or unschedule the recovery cron depending on settings.
	 * Runs on init so settings changes take effect without reactivation.
	 */
	public function maybe_schedule_cron() {
		$enabled = $this->is_configured()
			&& 'yes' === ( $this->options['enable_auto_recovery_send'] ?? 'no' )
			&& 'yes' === ( $this->options['enable_abandoned_cart'] ?? 'no' );

		$scheduled = wp_next_scheduled( self::CRON_HOOK );

		if ( $enabled && ! $scheduled ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'cubixsol_chat_15min', self::CRON_HOOK );
		} elseif ( ! $enabled && $scheduled ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
	}

	/**
	 * Cron worker: send recovery messages for eligible abandoned carts.
	 *
	 * Eligible = status "abandoned", has a phone, not yet sent, older than the
	 * configured delay, and no older than 7 days. Batched to 10 per run so a
	 * backlog never times out a request.
	 */
	public function cron_process_abandoned_carts() {
		if ( ! $this->is_configured() || 'yes' !== ( $this->options['enable_auto_recovery_send'] ?? 'no' ) ) {
			return;
		}

		global $wpdb;

		$delay_minutes = max( 5, absint( $this->options['recovery_send_delay'] ?? 60 ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, fresh rows required for sending.
		$carts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i
				WHERE cart_status = 'abandoned'
				AND customer_phone <> ''
				AND recovery_sent_at IS NULL
				AND created_at <= DATE_SUB( %s, INTERVAL %d MINUTE )
				AND created_at >= DATE_SUB( %s, INTERVAL 7 DAY )
				ORDER BY created_at ASC
				LIMIT 10",
				$wpdb->prefix . 'cubixsol_chat_abandoned_carts',
				current_time( 'mysql' ),
				$delay_minutes,
				current_time( 'mysql' )
			)
		);

		if ( empty( $carts ) ) {
			return;
		}

		foreach ( $carts as $cart ) {
			$result = $this->send_recovery_for_cart( $cart );

			if ( ! is_wp_error( $result ) ) {
				$this->mark_sent( $cart->id );
			} else {
				// Mark permanently-failing rows too, so one bad number can't
				// block the batch forever. Recipient-level errors are final.
				$code = $result->get_error_message();
				if ( false !== strpos( $code, '131026' ) || false !== strpos( $code, '131047' ) || false !== strpos( $code, '131030' ) ) {
					$this->mark_sent( $cart->id );
				}
			}
		}
	}
}
