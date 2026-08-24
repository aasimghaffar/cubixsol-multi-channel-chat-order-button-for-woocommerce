<?php
/**
 * WooCommerce Order WhatsApp Notifications Hub.
 * Queries and displays real WooCommerce orders from the database.
 *
 * @package    Cubixsol_Chat
 * @subpackage Cubixsol_Chat/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cubixsol_chat_is_wc_active = class_exists( 'WooCommerce' );
$cubixsol_chat_orders       = array();

if ( $cubixsol_chat_is_wc_active ) {
	$cubixsol_chat_orders = wc_get_orders(
		array(
			'limit'   => 20,
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	);
}

// Template resolved once, outside the loop ($options is provided by the admin class).
$cubixsol_chat_notify_template = $options['order_notify_template'] ?? "Hello {customer_name}! Your order #{order_id} on {site_title} is now {order_status}.\nTotal: {order_total}\nThank you for shopping with us!";
?>
<div class="wrap cubixsol-chat-admin-wrap">
	<div class="cubixsol-chat-admin-header">
		<div class="cubixsol-chat-brand-title">
			<div class="cubixsol-chat-logo-icon"><span class="dashicons dashicons-archive"></span></div>
			<div>
				<h1><?php esc_html_e( 'WooCommerce Order Alerts Hub', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h1>
				<p class="cubixsol-chat-version-tag"><?php esc_html_e( 'Send 1-click WhatsApp order confirmation and shipping updates to customers • By Cubixsol', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
			</div>
		</div>
		<div class="cubixsol-chat-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cubixsol-chat-settings' ) ); ?>" class="button button-secondary"><?php esc_html_e( '← Settings & Widget', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></a>
		</div>
	</div>

	<div class="cubixsol-chat-card">
		<div class="cubixsol-chat-card-head">
			<h3><?php esc_html_e( 'Live WooCommerce Orders & WhatsApp Notification Actions', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Notify customers about their order status and delivery updates directly on WhatsApp.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
		</div>

		<?php if ( ! $cubixsol_chat_is_wc_active ) : ?>
			<div class="cubixsol-chat-empty-state">
				<span class="dashicons dashicons-warning" style="font-size:36px;color:#f59e0b;height:36px;width:36px;margin-bottom:10px;"></span>
				<h4><?php esc_html_e( 'WooCommerce is not installed or active', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h4>
				<p class="description"><?php esc_html_e( 'Please install and activate the WooCommerce plugin to track and notify store customers via WhatsApp.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
			</div>
		<?php elseif ( empty( $cubixsol_chat_orders ) ) : ?>
			<div class="cubixsol-chat-empty-state">
				<span class="dashicons dashicons-cart" style="font-size:36px;color:#94a3b8;height:36px;width:36px;margin-bottom:10px;"></span>
				<h4><?php esc_html_e( 'No WooCommerce orders found yet', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h4>
				<p class="description"><?php esc_html_e( 'When customers place orders in your store, they will automatically appear here with 1-click WhatsApp messaging actions.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
			</div>
		<?php else : ?>
			<div class="cubixsol-chat-table-container">
				<table class="wp-list-table widefat striped cubixsol-chat-recovery-table">
					<thead>
						<tr>
							<th style="width:12%;"><?php esc_html_e( 'Order ID', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:20%;"><?php esc_html_e( 'Customer Name', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:18%;"><?php esc_html_e( 'WhatsApp Phone', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:14%;"><?php esc_html_e( 'Order Total', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:14%;"><?php esc_html_e( 'Order Status', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:22%; text-align:right;"><?php esc_html_e( '1-Click Action', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $cubixsol_chat_orders as $cubixsol_chat_order ) :
							$cubixsol_chat_order_id      = $cubixsol_chat_order->get_id();
							$cubixsol_chat_customer_name = $cubixsol_chat_order->get_formatted_billing_full_name();
							if ( '' === trim( (string) $cubixsol_chat_customer_name ) ) {
								$cubixsol_chat_customer_name = __( 'Guest Customer', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
							}
							$cubixsol_chat_phone       = $cubixsol_chat_order->get_billing_phone();
							$cubixsol_chat_clean_phone = preg_replace( '/[^0-9]/', '', (string) $cubixsol_chat_phone );
							$cubixsol_chat_total_plain = html_entity_decode( wp_strip_all_tags( $cubixsol_chat_order->get_formatted_order_total() ) );
							$cubixsol_chat_status_name = wc_get_order_status_name( $cubixsol_chat_order->get_status() );
							$cubixsol_chat_status_slug = $cubixsol_chat_order->get_status();

							$cubixsol_chat_msg = str_replace(
								array( '{customer_name}', '{order_id}', '{site_title}', '{order_status}', '{order_total}' ),
								array(
									$cubixsol_chat_order->get_billing_first_name() ? $cubixsol_chat_order->get_billing_first_name() : $cubixsol_chat_customer_name,
									$cubixsol_chat_order_id,
									get_bloginfo( 'name' ),
									$cubixsol_chat_status_name,
									$cubixsol_chat_total_plain,
								),
								$cubixsol_chat_notify_template
							);

							$cubixsol_chat_wa_url = ! empty( $cubixsol_chat_clean_phone ) ? 'https://wa.me/' . $cubixsol_chat_clean_phone . '?text=' . rawurlencode( $cubixsol_chat_msg ) : '#';
							?>
						<tr>
							<td><strong>#<?php echo esc_html( $cubixsol_chat_order_id ); ?></strong></td>
							<td><strong><?php echo esc_html( $cubixsol_chat_customer_name ); ?></strong></td>
							<td>
								<?php if ( ! empty( $cubixsol_chat_phone ) ) : ?>
									<span class="cubixsol-chat-phone-pill"><?php echo esc_html( $cubixsol_chat_phone ); ?></span>
								<?php else : ?>
									<span class="text-muted"><?php esc_html_e( 'No phone provided', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
								<?php endif; ?>
							</td>
							<td><strong><?php echo esc_html( $cubixsol_chat_total_plain ); ?></strong></td>
							<td>
								<span class="cubixsol-chat-badge-status cubixsol-chat-status-<?php echo esc_attr( $cubixsol_chat_status_slug ); ?>">
									<?php echo esc_html( $cubixsol_chat_status_name ); ?>
								</span>
							</td>
							<td style="text-align:right;">
								<?php if ( ! empty( $cubixsol_chat_clean_phone ) ) : ?>
									<a href="<?php echo esc_url( $cubixsol_chat_wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary cubixsol-chat-wa-send-btn">
										<?php esc_html_e( 'Send WhatsApp Update', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?>
									</a>
								<?php else : ?>
									<button type="button" class="button button-secondary" disabled><?php esc_html_e( 'No Phone', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></button>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
