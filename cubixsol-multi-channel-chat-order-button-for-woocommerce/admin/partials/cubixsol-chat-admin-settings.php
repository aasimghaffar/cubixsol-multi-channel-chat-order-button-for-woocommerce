<?php
/**
 * Widget & Settings Admin Page Partial View.
 * Tabbed settings UI: Widget, Agents, WooCommerce Button, Messages & Recovery, API & Tracking.
 *
 * @package    Cubixsol_Chat
 * @subpackage Cubixsol_Chat/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cubixsol_chat_agents = isset( $options['agents'] ) && is_array( $options['agents'] ) ? $options['agents'] : array();
?>
<div class="wrap cubixsol-chat-admin-wrap">

	<!-- Header -->
	<div class="cubixsol-chat-admin-header">
		<div class="cubixsol-chat-brand-title">
			<div class="cubixsol-chat-logo-icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
			</div>
			<div>
				<h1><?php esc_html_e( 'Multi-Channel Chat & Order', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h1>
				<p class="cubixsol-chat-version-tag">
					<?php
					/* translators: %s: plugin version number. */
					printf( esc_html__( 'Version %s • By Cubixsol • ', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ), esc_html( CUBIXSOL_CHAT_VERSION ) );
					?>
					<span class="cubixsol-chat-full-unlocked-badge"><?php esc_html_e( '100% Features Unlocked', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
				</p>
			</div>
		</div>
		<div class="cubixsol-chat-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cubixsol-chat-recovery' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cart Recovery Log', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cubixsol-chat-orders' ) ); ?>" class="button button-primary cubixsol-chat-btn-green"><?php esc_html_e( 'Order Alerts Hub', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></a>
		</div>
	</div>

	<?php settings_errors(); ?>

	<!-- Tabs -->
	<div class="cubixsol-chat-tab-navigation">
		<a href="#" class="cubixsol-chat-tab-link active" data-tab="cubixsol-chat-tab-widget"><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e( 'Floating Widget', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></a>
		<a href="#" class="cubixsol-chat-tab-link" data-tab="cubixsol-chat-tab-agents"><span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Agents & Channels', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></a>
		<a href="#" class="cubixsol-chat-tab-link" data-tab="cubixsol-chat-tab-woo"><span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'WooCommerce Button', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></a>
		<a href="#" class="cubixsol-chat-tab-link" data-tab="cubixsol-chat-tab-messages"><span class="dashicons dashicons-email"></span> <?php esc_html_e( 'Messages & Recovery', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></a>
		<a href="#" class="cubixsol-chat-tab-link" data-tab="cubixsol-chat-tab-api"><span class="dashicons dashicons-chart-line"></span> <?php esc_html_e( 'API & Tracking', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></a>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'cubixsol_chat_options_group' ); ?>

		<!-- TAB 1: Floating Widget -->
		<div id="cubixsol-chat-tab-widget" class="cubixsol-chat-tab-content active">
			<div class="cubixsol-chat-card">
				<div class="cubixsol-chat-card-head">
					<h3><?php esc_html_e( 'Floating Chat Widget', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Configure the floating chat button shown on your storefront.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
				</div>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Floating Widget', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<td>
							<label class="cubixsol-chat-switch">
								<input type="checkbox" name="cubixsol_chat_settings[enable_floating_widget]" value="yes" <?php checked( $options['enable_floating_widget'], 'yes' ); ?>>
								<span class="cubixsol-chat-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-default-phone"><?php esc_html_e( 'Default WhatsApp Number', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td>
							<input type="text" id="cubixsol-chat-default-phone" class="regular-text" name="cubixsol_chat_settings[default_phone]" value="<?php echo esc_attr( $options['default_phone'] ); ?>" placeholder="+15551234567">
							<p class="description"><?php esc_html_e( 'Include the country code, e.g. +447712345678. Used when no agent is selected.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-widget-position"><?php esc_html_e( 'Widget Position', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td>
							<select id="cubixsol-chat-widget-position" name="cubixsol_chat_settings[widget_position]">
								<option value="bottom-right" <?php selected( $options['widget_position'], 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
								<option value="bottom-left" <?php selected( $options['widget_position'], 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-theme-color"><?php esc_html_e( 'Widget Theme Color', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="color" id="cubixsol-chat-theme-color" name="cubixsol_chat_settings[widget_theme_color]" value="<?php echo esc_attr( $options['widget_theme_color'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-cta-text"><?php esc_html_e( 'Button CTA Text', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="cubixsol-chat-cta-text" class="regular-text" name="cubixsol_chat_settings[button_cta_text]" value="<?php echo esc_attr( $options['button_cta_text'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-greeting"><?php esc_html_e( 'Greeting Message', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="cubixsol-chat-greeting" class="large-text" name="cubixsol_chat_settings[widget_greeting]" value="<?php echo esc_attr( $options['widget_greeting'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-header-title"><?php esc_html_e( 'Popup Header Title', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="cubixsol-chat-header-title" class="regular-text" name="cubixsol_chat_settings[popup_header_title]" value="<?php echo esc_attr( $options['popup_header_title'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-header-subtitle"><?php esc_html_e( 'Popup Header Subtitle', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="cubixsol-chat-header-subtitle" class="regular-text" name="cubixsol_chat_settings[popup_header_subtitle]" value="<?php echo esc_attr( $options['popup_header_subtitle'] ); ?>"></td>
					</tr>
				</table>
				<input type="hidden" name="cubixsol_chat_settings[button_icon]" value="<?php echo esc_attr( $options['button_icon'] ); ?>">
			</div>
		</div>

		<!-- TAB 2: Agents -->
		<div id="cubixsol-chat-tab-agents" class="cubixsol-chat-tab-content">
			<div class="cubixsol-chat-card">
				<div class="cubixsol-chat-card-head">
					<h3><?php esc_html_e( 'Support Agents & Channels', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Add multiple agents with individual channels (WhatsApp, Telegram, Phone) and working hours. Online/Away status is shown automatically based on working hours.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
				</div>

				<div id="cubixsol-chat-agents-list">
					<?php
					if ( ! empty( $cubixsol_chat_agents ) ) :
						foreach ( $cubixsol_chat_agents as $cubixsol_chat_i => $cubixsol_chat_agent ) :
							?>
						<div class="cubixsol-chat-agent-box">
							<div class="cubixsol-chat-agent-fields-grid">
								<div>
									<label><?php esc_html_e( 'Agent Name', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
									<input type="text" class="widefat" name="cubixsol_chat_settings[agents][<?php echo esc_attr( $cubixsol_chat_i ); ?>][name]" value="<?php echo esc_attr( $cubixsol_chat_agent['name'] ?? '' ); ?>">
								</div>
								<div>
									<label><?php esc_html_e( 'Role', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
									<input type="text" class="widefat" name="cubixsol_chat_settings[agents][<?php echo esc_attr( $cubixsol_chat_i ); ?>][role]" value="<?php echo esc_attr( $cubixsol_chat_agent['role'] ?? '' ); ?>">
								</div>
								<div>
									<label><?php esc_html_e( 'Channel', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
									<select class="widefat" name="cubixsol_chat_settings[agents][<?php echo esc_attr( $cubixsol_chat_i ); ?>][channel]">
										<option value="whatsapp" <?php selected( $cubixsol_chat_agent['channel'] ?? 'whatsapp', 'whatsapp' ); ?>><?php esc_html_e( 'WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
										<option value="telegram" <?php selected( $cubixsol_chat_agent['channel'] ?? '', 'telegram' ); ?>><?php esc_html_e( 'Telegram', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
										<option value="phone" <?php selected( $cubixsol_chat_agent['channel'] ?? '', 'phone' ); ?>><?php esc_html_e( 'Phone Call', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
									</select>
								</div>
								<div>
									<label><?php esc_html_e( 'Phone / Username', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
									<input type="text" class="widefat" name="cubixsol_chat_settings[agents][<?php echo esc_attr( $cubixsol_chat_i ); ?>][phone]" value="<?php echo esc_attr( $cubixsol_chat_agent['phone'] ?? '' ); ?>">
								</div>
								<div>
									<label><?php esc_html_e( 'Hours Start', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
									<input type="time" class="widefat" name="cubixsol_chat_settings[agents][<?php echo esc_attr( $cubixsol_chat_i ); ?>][hours_start]" value="<?php echo esc_attr( $cubixsol_chat_agent['hours_start'] ?? '09:00' ); ?>">
								</div>
								<div>
									<label><?php esc_html_e( 'Hours End', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
									<input type="time" class="widefat" name="cubixsol_chat_settings[agents][<?php echo esc_attr( $cubixsol_chat_i ); ?>][hours_end]" value="<?php echo esc_attr( $cubixsol_chat_agent['hours_end'] ?? '18:00' ); ?>">
								</div>
								<div>
									<label><?php esc_html_e( 'Status', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
									<select class="widefat" name="cubixsol_chat_settings[agents][<?php echo esc_attr( $cubixsol_chat_i ); ?>][status]">
										<option value="online" <?php selected( $cubixsol_chat_agent['status'] ?? 'online', 'online' ); ?>><?php esc_html_e( 'Online', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
										<option value="offline" <?php selected( $cubixsol_chat_agent['status'] ?? '', 'offline' ); ?>><?php esc_html_e( 'Offline', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
									</select>
								</div>
								<div style="display:flex;align-items:flex-end;">
									<button type="button" class="button button-link-delete cubixsol-chat-remove-agent"><?php esc_html_e( 'Remove', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></button>
								</div>
							</div>
						</div>
							<?php
						endforeach;
					endif;
					?>
				</div>

				<button type="button" id="cubixsol-chat-add-agent" class="button button-secondary"><?php esc_html_e( '+ Add Agent', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></button>

				<!-- Blank row template used by admin JS. -->
				<script type="text/template" id="cubixsol-chat-agent-template">
					<div class="cubixsol-chat-agent-box">
						<div class="cubixsol-chat-agent-fields-grid">
							<div>
								<label><?php esc_html_e( 'Agent Name', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
								<input type="text" class="widefat" name="cubixsol_chat_settings[agents][__INDEX__][name]" value="">
							</div>
							<div>
								<label><?php esc_html_e( 'Role', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
								<input type="text" class="widefat" name="cubixsol_chat_settings[agents][__INDEX__][role]" value="">
							</div>
							<div>
								<label><?php esc_html_e( 'Channel', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
								<select class="widefat" name="cubixsol_chat_settings[agents][__INDEX__][channel]">
									<option value="whatsapp"><?php esc_html_e( 'WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
									<option value="telegram"><?php esc_html_e( 'Telegram', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
									<option value="phone"><?php esc_html_e( 'Phone Call', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
								</select>
							</div>
							<div>
								<label><?php esc_html_e( 'Phone / Username', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
								<input type="text" class="widefat" name="cubixsol_chat_settings[agents][__INDEX__][phone]" value="">
							</div>
							<div>
								<label><?php esc_html_e( 'Hours Start', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
								<input type="time" class="widefat" name="cubixsol_chat_settings[agents][__INDEX__][hours_start]" value="09:00">
							</div>
							<div>
								<label><?php esc_html_e( 'Hours End', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
								<input type="time" class="widefat" name="cubixsol_chat_settings[agents][__INDEX__][hours_end]" value="18:00">
							</div>
							<div>
								<label><?php esc_html_e( 'Status', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
								<select class="widefat" name="cubixsol_chat_settings[agents][__INDEX__][status]">
									<option value="online"><?php esc_html_e( 'Online', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
									<option value="offline"><?php esc_html_e( 'Offline', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></option>
								</select>
							</div>
							<div style="display:flex;align-items:flex-end;">
								<button type="button" class="button button-link-delete cubixsol-chat-remove-agent"><?php esc_html_e( 'Remove', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></button>
							</div>
						</div>
					</div>
				</script>
			</div>
		</div>

		<!-- TAB 3: WooCommerce Button -->
		<div id="cubixsol-chat-tab-woo" class="cubixsol-chat-tab-content">
			<div class="cubixsol-chat-card">
				<div class="cubixsol-chat-card-head">
					<h3><?php esc_html_e( '1-Click "Order via WhatsApp" Button', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Shows a WhatsApp order button on single product pages with the product name, price, SKU and URL pre-filled.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
				</div>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Product Button', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<td>
							<label class="cubixsol-chat-switch">
								<input type="checkbox" name="cubixsol_chat_settings[enable_woo_order_btn]" value="yes" <?php checked( $options['enable_woo_order_btn'], 'yes' ); ?>>
								<span class="cubixsol-chat-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-woo-btn-text"><?php esc_html_e( 'Button Text', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="cubixsol-chat-woo-btn-text" class="regular-text" name="cubixsol_chat_settings[woo_btn_text]" value="<?php echo esc_attr( $options['woo_btn_text'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Button Position', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<td>
							<label class="cubixsol-chat-radio-label"><input type="radio" name="cubixsol_chat_settings[woo_btn_position]" value="after_add_to_cart" <?php checked( $options['woo_btn_position'], 'after_add_to_cart' ); ?>> <?php esc_html_e( 'After "Add to Cart" button', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
							<label class="cubixsol-chat-radio-label"><input type="radio" name="cubixsol_chat_settings[woo_btn_position]" value="before_add_to_cart" <?php checked( $options['woo_btn_position'], 'before_add_to_cart' ); ?>> <?php esc_html_e( 'Before "Add to Cart" button', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-woo-btn-color"><?php esc_html_e( 'Button Background Color', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="color" id="cubixsol-chat-woo-btn-color" name="cubixsol_chat_settings[woo_btn_bg_color]" value="<?php echo esc_attr( $options['woo_btn_bg_color'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-woo-msg"><?php esc_html_e( 'Order Message Template', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td>
							<textarea id="cubixsol-chat-woo-msg" class="large-text" rows="5" name="cubixsol_chat_settings[woo_message_template]"><?php echo esc_textarea( $options['woo_message_template'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Placeholders: {product_name}, {product_price}, {product_sku}, {product_url}', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- TAB 4: Messages & Recovery -->
		<div id="cubixsol-chat-tab-messages" class="cubixsol-chat-tab-content">
			<div class="cubixsol-chat-card">
				<div class="cubixsol-chat-card-head">
					<h3><?php esc_html_e( 'Order Status Notification Template', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Used by the 1-click WhatsApp buttons on the WooCommerce Orders list and the Order Alerts Hub.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
				</div>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cubixsol-chat-order-template"><?php esc_html_e( 'Message Template', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td>
							<textarea id="cubixsol-chat-order-template" class="large-text" rows="4" name="cubixsol_chat_settings[order_notify_template]"><?php echo esc_textarea( $options['order_notify_template'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Placeholders: {customer_name}, {order_id}, {site_title}, {order_status}, {order_total}', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="cubixsol-chat-card">
				<div class="cubixsol-chat-card-head">
					<h3><?php esc_html_e( 'Abandoned Cart Recovery', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Captures checkout sessions in real time so you can recover abandoned carts via WhatsApp.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
				</div>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Cart Capturing', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<td>
							<label class="cubixsol-chat-switch">
								<input type="checkbox" name="cubixsol_chat_settings[enable_abandoned_cart]" value="yes" <?php checked( $options['enable_abandoned_cart'], 'yes' ); ?>>
								<span class="cubixsol-chat-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-recovery-msg"><?php esc_html_e( 'Recovery Message Template', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td>
							<textarea id="cubixsol-chat-recovery-msg" class="large-text" rows="4" name="cubixsol_chat_settings[abandoned_cart_msg]"><?php echo esc_textarea( $options['abandoned_cart_msg'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Placeholders: {customer_name}, {site_title}, {cart_url}', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- TAB 5: API & Tracking -->
		<div id="cubixsol-chat-tab-api" class="cubixsol-chat-tab-content">
			<div class="cubixsol-chat-card">
				<div class="cubixsol-chat-card-head">
					<h3><?php esc_html_e( 'Sending Mode', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Direct mode opens WhatsApp chat links (wa.me) and requires no API. Meta Cloud API mode sends messages from your server through the official WhatsApp Business API — used for automated cart recovery and manual sends from the Recovery page.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
				</div>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Mode', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<td>
							<label class="cubixsol-chat-radio-label"><input type="radio" name="cubixsol_chat_settings[sending_mode]" value="direct" <?php checked( $options['sending_mode'], 'direct' ); ?>> <?php esc_html_e( 'Direct (wa.me links, no API required)', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
							<label class="cubixsol-chat-radio-label"><input type="radio" name="cubixsol_chat_settings[sending_mode]" value="meta_api" <?php checked( $options['sending_mode'], 'meta_api' ); ?>> <?php esc_html_e( 'Meta WhatsApp Cloud API', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-meta-token"><?php esc_html_e( 'Meta API Token', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="password" id="cubixsol-chat-meta-token" class="regular-text" name="cubixsol_chat_settings[meta_api_token]" value="<?php echo esc_attr( $options['meta_api_token'] ); ?>" autocomplete="off"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-meta-phone-id"><?php esc_html_e( 'Meta Phone Number ID', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="cubixsol-chat-meta-phone-id" class="regular-text" name="cubixsol_chat_settings[meta_phone_number_id]" value="<?php echo esc_attr( $options['meta_phone_number_id'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-meta-template"><?php esc_html_e( 'Message Template Name', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td>
							<input type="text" id="cubixsol-chat-meta-template" class="regular-text" name="cubixsol_chat_settings[meta_template_name]" value="<?php echo esc_attr( $options['meta_template_name'] ); ?>" placeholder="cart_recovery">
							<p class="description"><?php esc_html_e( 'Name of a template approved in Meta Business Manager, with {{1}} = customer name and {{2}} = cart link. Recommended: WhatsApp only delivers free-text messages within 24 hours of the customer messaging you first — templates deliver any time. Leave empty to send the plain "Abandoned cart message" text instead.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-meta-template-lang"><?php esc_html_e( 'Template Language Code', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td><input type="text" id="cubixsol-chat-meta-template-lang" class="small-text" style="width:110px" name="cubixsol_chat_settings[meta_template_lang]" value="<?php echo esc_attr( $options['meta_template_lang'] ); ?>" placeholder="en_US"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Auto-send Recovery Messages', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<td>
							<label class="cubixsol-chat-toggle">
								<input type="checkbox" name="cubixsol_chat_settings[enable_auto_recovery_send]" value="yes" <?php checked( $options['enable_auto_recovery_send'], 'yes' ); ?>>
								<span class="cubixsol-chat-toggle-slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Automatically send the recovery message to abandoned carts (runs every 15 minutes, max 10 per run, carts no older than 7 days). Off by default — no message is ever sent without turning this on.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cubixsol-chat-recovery-delay"><?php esc_html_e( 'Send After (minutes)', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td>
							<input type="number" id="cubixsol-chat-recovery-delay" class="small-text" name="cubixsol_chat_settings[recovery_send_delay]" value="<?php echo esc_attr( $options['recovery_send_delay'] ); ?>" min="5" max="10080" step="1">
							<p class="description"><?php esc_html_e( 'How long a cart must sit abandoned before the automatic message goes out (5–10080).', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="cubixsol-chat-card">
				<div class="cubixsol-chat-card-head">
					<h3><?php esc_html_e( 'Test Your API Connection', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Save your settings above first, then send a test. With no template configured, the built-in "hello_world" template is used — every Meta test number has it pre-approved. The recipient must be a verified test recipient while your Meta app is in development mode.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
				</div>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cubixsol-chat-test-phone"><?php esc_html_e( 'Recipient Phone', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></label></th>
						<td>
							<input type="text" id="cubixsol-chat-test-phone" class="regular-text" placeholder="+15551234567">
							<button type="button" class="button button-secondary" id="cubixsol-chat-send-test-btn"><?php esc_html_e( 'Send Test Message', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></button>
							<p class="description" id="cubixsol-chat-test-result" aria-live="polite"></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="cubixsol-chat-card">
				<div class="cubixsol-chat-card-head">
					<h3><?php esc_html_e( 'Conversion Tracking', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Fires click events into your existing Google Analytics 4 (gtag) and Meta Pixel (fbq) installations. No extra scripts are loaded by this plugin.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
				</div>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Google Analytics 4 Events', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<td>
							<label class="cubixsol-chat-switch">
								<input type="checkbox" name="cubixsol_chat_settings[enable_ga4_tracking]" value="yes" <?php checked( $options['enable_ga4_tracking'], 'yes' ); ?>>
								<span class="cubixsol-chat-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Meta Pixel Events', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<td>
							<label class="cubixsol-chat-switch">
								<input type="checkbox" name="cubixsol_chat_settings[enable_fb_tracking]" value="yes" <?php checked( $options['enable_fb_tracking'], 'yes' ); ?>>
								<span class="cubixsol-chat-slider"></span>
							</label>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<?php submit_button( __( 'Save All Settings', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ); ?>
	</form>
</div>
