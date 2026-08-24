<?php
/**
 * Cart Recovery Table & Category Analytics Partial View.
 * Queries real captured checkout sessions and WooCommerce database orders.
 *
 * @package    Cubixsol_Chat
 * @subpackage Cubixsol_Chat/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

/**
 * Print a money amount (wc_price returns safe HTML).
 *
 * @param float $amount Amount.
 */
$cubixsol_chat_meta_api_ready = false;
if ( class_exists( 'Cubixsol_Chat_Meta_API' ) ) {
	$cubixsol_chat_meta_api_obj   = new Cubixsol_Chat_Meta_API();
	$cubixsol_chat_meta_api_ready = $cubixsol_chat_meta_api_obj->is_configured();
}

if ( ! function_exists( 'cubixsol_chat_admin_price' ) ) {
	function cubixsol_chat_admin_price( $amount ) {
		if ( function_exists( 'wc_price' ) ) {
			echo wp_kses_post( wc_price( $amount ) );
		} else {
			echo esc_html( '$' . number_format( (float) $amount, 2 ) );
		}
	}
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, live admin data.
$cubixsol_chat_captured_sessions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cubixsol_chat_abandoned_carts ORDER BY updated_at DESC LIMIT 100" );

$cubixsol_chat_is_wc_active = class_exists( 'WooCommerce' );

$cubixsol_chat_pending_count       = 0;
$cubixsol_chat_recovered_count     = 0;
$cubixsol_chat_total_abandoned_val = 0.00;
$cubixsol_chat_total_recovered_val = 0.00;
$cubixsol_chat_category_stats      = array();
$cubixsol_chat_table_rows          = array();

// Process captured sessions.
if ( ! empty( $cubixsol_chat_captured_sessions ) ) {
	foreach ( $cubixsol_chat_captured_sessions as $cubixsol_chat_row ) {
		$cubixsol_chat_contents  = json_decode( $cubixsol_chat_row->cart_contents, true );
		$cubixsol_chat_items_arr = array();
		$cubixsol_chat_cat_arr   = array();

		if ( is_array( $cubixsol_chat_contents ) ) {
			foreach ( $cubixsol_chat_contents as $cubixsol_chat_item ) {
				$cubixsol_chat_items_arr[] = ( $cubixsol_chat_item['name'] ?? __( 'Product', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ) . ' (x' . ( $cubixsol_chat_item['quantity'] ?? 1 ) . ')';
				$cubixsol_chat_cat_name    = $cubixsol_chat_item['category'] ?? __( 'General', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
				$cubixsol_chat_cat_arr[]   = $cubixsol_chat_cat_name;

				if ( ! isset( $cubixsol_chat_category_stats[ $cubixsol_chat_cat_name ] ) ) {
					$cubixsol_chat_category_stats[ $cubixsol_chat_cat_name ] = array(
						'abandoned' => 0,
						'lost_val'  => 0,
						'recovered' => 0,
						'rec_val'   => 0,
					);
				}
				if ( 'recovered' === $cubixsol_chat_row->cart_status ) {
					$cubixsol_chat_category_stats[ $cubixsol_chat_cat_name ]['recovered']++;
					$cubixsol_chat_category_stats[ $cubixsol_chat_cat_name ]['rec_val'] += floatval( $cubixsol_chat_item['price'] ?? 0 );
				} else {
					$cubixsol_chat_category_stats[ $cubixsol_chat_cat_name ]['abandoned']++;
					$cubixsol_chat_category_stats[ $cubixsol_chat_cat_name ]['lost_val'] += floatval( $cubixsol_chat_item['price'] ?? 0 );
				}
			}
		}

		$cubixsol_chat_is_rec = ( 'recovered' === $cubixsol_chat_row->cart_status );
		if ( $cubixsol_chat_is_rec ) {
			$cubixsol_chat_recovered_count++;
			$cubixsol_chat_total_recovered_val += floatval( $cubixsol_chat_row->cart_total );
		} else {
			$cubixsol_chat_pending_count++;
			$cubixsol_chat_total_abandoned_val += floatval( $cubixsol_chat_row->cart_total );
		}

		$cubixsol_chat_table_rows[] = array(
			'id'       => (int) $cubixsol_chat_row->id,
			'name'     => ! empty( $cubixsol_chat_row->customer_name ) ? $cubixsol_chat_row->customer_name : __( 'Guest Customer', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'email'    => $cubixsol_chat_row->customer_email,
			'phone'    => $cubixsol_chat_row->customer_phone,
			'items'    => ! empty( $cubixsol_chat_items_arr ) ? implode( ', ', $cubixsol_chat_items_arr ) : __( 'Cart Items', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'category' => ! empty( $cubixsol_chat_cat_arr ) ? implode( ' • ', array_unique( $cubixsol_chat_cat_arr ) ) : __( 'General', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'total'    => floatval( $cubixsol_chat_row->cart_total ),
			'status'   => $cubixsol_chat_row->cart_status,
			'time_ago' => human_time_diff( strtotime( $cubixsol_chat_row->updated_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'api_sent' => ! empty( $cubixsol_chat_row->recovery_sent_at ),
		);
	}
}

// Fallback: WooCommerce pending/failed orders when the capture table is fresh.
if ( $cubixsol_chat_is_wc_active && empty( $cubixsol_chat_table_rows ) ) {
	$cubixsol_chat_wc_pending = wc_get_orders(
		array(
			'status' => array( 'pending', 'failed', 'on-hold' ),
			'limit'  => 20,
		)
	);

	foreach ( $cubixsol_chat_wc_pending as $cubixsol_chat_o ) {
		$cubixsol_chat_pending_count++;
		$cubixsol_chat_total_abandoned_val += floatval( $cubixsol_chat_o->get_total() );
		$cubixsol_chat_table_rows[]         = array(
			'id'       => 0, // Not a captured-cart row; mark-recovered not applicable.
			'name'     => $cubixsol_chat_o->get_formatted_billing_full_name() ? $cubixsol_chat_o->get_formatted_billing_full_name() : __( 'Guest Customer', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'email'    => $cubixsol_chat_o->get_billing_email(),
			'phone'    => $cubixsol_chat_o->get_billing_phone(),
			'items'    => sprintf( /* translators: %d: order ID. */ __( 'Order #%d Items', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ), $cubixsol_chat_o->get_id() ),
			'category' => __( 'Store Products', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'total'    => floatval( $cubixsol_chat_o->get_total() ),
			'status'   => 'abandoned',
			'time_ago' => human_time_diff( strtotime( $cubixsol_chat_o->get_date_created() ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ),
			'api_sent' => false,
		);
	}
}

$cubixsol_chat_total_carts = $cubixsol_chat_pending_count + $cubixsol_chat_recovered_count;
$cubixsol_chat_win_rate    = $cubixsol_chat_total_carts > 0 ? round( ( $cubixsol_chat_recovered_count / $cubixsol_chat_total_carts ) * 100, 1 ) : 0;

// Message template resolved once, outside the row loop.
$cubixsol_chat_recovery_template = $options['abandoned_cart_msg'] ?? "Hi {customer_name}! We noticed you left items in your cart on {site_title}. Use coupon code 'SAVE10' for 10% off your order today! Click here to resume: {cart_url}";
?>
<div class="wrap cubixsol-chat-admin-wrap">
	<!-- Header -->
	<div class="cubixsol-chat-admin-header">
		<div class="cubixsol-chat-brand-title">
			<div class="cubixsol-chat-logo-icon"><span class="dashicons dashicons-cart"></span></div>
			<div>
				<h1><?php esc_html_e( 'Abandoned Cart Recovery & Analytics', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h1>
				<p class="cubixsol-chat-version-tag"><?php esc_html_e( 'Real-time captured checkout sessions • 1-click WhatsApp customer recovery • By Cubixsol', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
			</div>
		</div>
		<div class="cubixsol-chat-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cubixsol-chat-settings' ) ); ?>" class="button button-secondary"><?php esc_html_e( '← Settings & Widget', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></a>
			<?php if ( ! empty( $cubixsol_chat_table_rows ) ) : ?>
				<button type="button" id="cubixsol-chat-export-csv-btn" class="button button-primary cubixsol-chat-btn-green">
					<?php esc_html_e( 'Export Leads (CSV)', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<!-- Metric Cards -->
	<div class="cubixsol-chat-stats-grid">
		<div class="cubixsol-chat-stat-card">
			<span class="cubixsol-chat-stat-label"><?php esc_html_e( 'Pending Recovery Carts', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
			<div class="cubixsol-chat-stat-value"><?php echo esc_html( $cubixsol_chat_pending_count ); ?> <span class="cubixsol-chat-stat-sub">(<?php cubixsol_chat_admin_price( $cubixsol_chat_total_abandoned_val ); ?>)</span></div>
		</div>
		<div class="cubixsol-chat-stat-card cubixsol-chat-stat-success">
			<span class="cubixsol-chat-stat-label"><?php esc_html_e( 'Recovered Orders', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
			<div class="cubixsol-chat-stat-value"><?php echo esc_html( $cubixsol_chat_recovered_count ); ?> <span class="cubixsol-chat-stat-sub">(+<?php cubixsol_chat_admin_price( $cubixsol_chat_total_recovered_val ); ?>)</span></div>
		</div>
		<div class="cubixsol-chat-stat-card cubixsol-chat-stat-highlight">
			<span class="cubixsol-chat-stat-label"><?php esc_html_e( 'Recovery Win Rate', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
			<div class="cubixsol-chat-stat-value"><?php echo esc_html( $cubixsol_chat_win_rate ); ?>% <span class="cubixsol-chat-stat-sub"><?php esc_html_e( 'Success Rate', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span></div>
		</div>
		<div class="cubixsol-chat-stat-card cubixsol-chat-stat-warning">
			<span class="cubixsol-chat-stat-label"><?php esc_html_e( 'Total Captured Leads', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
			<div class="cubixsol-chat-stat-value"><?php echo esc_html( $cubixsol_chat_total_carts ); ?> <span class="cubixsol-chat-stat-sub"><?php esc_html_e( 'Sessions in DB', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span></div>
		</div>
	</div>

	<!-- Category Breakdown -->
	<?php if ( ! empty( $cubixsol_chat_category_stats ) ) : ?>
	<div class="cubixsol-chat-card">
		<div class="cubixsol-chat-card-head">
			<h3><?php esc_html_e( 'Live Category-Wise Performance Analysis', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Category metrics computed dynamically from your store checkout cart items.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
		</div>

		<div class="cubixsol-chat-table-container">
			<table class="wp-list-table widefat striped cubixsol-chat-cat-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Product Category', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Pending Carts', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Pending Revenue', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Recovered Orders', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Recovered Revenue', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Success Rate', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $cubixsol_chat_category_stats as $cat => $cubixsol_chat_data ) :
						$cubixsol_chat_c_total = $cubixsol_chat_data['abandoned'] + $cubixsol_chat_data['recovered'];
						$cubixsol_chat_c_rate  = $cubixsol_chat_c_total > 0 ? round( ( $cubixsol_chat_data['recovered'] / $cubixsol_chat_c_total ) * 100, 1 ) : 0;
						?>
					<tr>
						<td><strong><?php echo esc_html( $cat ); ?></strong></td>
						<td><?php echo esc_html( $cubixsol_chat_data['abandoned'] ); ?> <?php esc_html_e( 'carts', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></td>
						<td><span class="text-danger"><?php cubixsol_chat_admin_price( $cubixsol_chat_data['lost_val'] ); ?></span></td>
						<td><span class="text-success"><?php echo esc_html( $cubixsol_chat_data['recovered'] ); ?> <?php esc_html_e( 'orders', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span></td>
						<td><span class="text-success">+<?php cubixsol_chat_admin_price( $cubixsol_chat_data['rec_val'] ); ?></span></td>
						<td>
							<div class="cubixsol-chat-progress-bar"><div class="cubixsol-chat-progress-fill" style="width:<?php echo esc_attr( $cubixsol_chat_c_rate ); ?>%;"></div></div>
							<strong><?php echo esc_html( $cubixsol_chat_c_rate ); ?>%</strong>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

	<!-- Live Abandoned Carts Log -->
	<div class="cubixsol-chat-card">
		<div class="cubixsol-chat-card-head">
			<h3><?php esc_html_e( 'Live Abandoned Carts & Recovery Queue', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Captured in real-time when visitors enter their details at checkout. Send direct WhatsApp recovery messages in 1 click.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
		</div>

		<?php if ( empty( $cubixsol_chat_table_rows ) ) : ?>
			<div class="cubixsol-chat-empty-state">
				<span class="dashicons dashicons-cart" style="font-size:36px;color:#94a3b8;height:36px;width:36px;margin-bottom:10px;"></span>
				<h4><?php esc_html_e( 'No abandoned carts captured yet', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></h4>
				<p class="description"><?php esc_html_e( 'When a visitor enters their phone number or email on the WooCommerce checkout page and leaves without completing the purchase, their abandoned cart will appear here instantly.', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></p>
			</div>
		<?php else : ?>
			<!-- Filter Bar -->
			<div class="cubixsol-chat-filter-toolbar">
				<div class="cubixsol-chat-filter-group">
					<button type="button" class="cubixsol-chat-filter-pill active" data-filter="all">
						<?php
						/* translators: %d: total number of carts. */
						printf( esc_html__( 'All Carts (%d)', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ), (int) count( $cubixsol_chat_table_rows ) );
						?>
					</button>
					<button type="button" class="cubixsol-chat-filter-pill" data-filter="abandoned">
						<?php
						/* translators: %d: number of pending carts. */
						printf( esc_html__( 'Pending Recovery (%d)', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ), (int) $cubixsol_chat_pending_count );
						?>
					</button>
					<button type="button" class="cubixsol-chat-filter-pill" data-filter="recovered">
						<?php
						/* translators: %d: number of recovered carts. */
						printf( esc_html__( 'Recovered (%d)', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ), (int) $cubixsol_chat_recovered_count );
						?>
					</button>
				</div>

				<div class="cubixsol-chat-search-wrapper">
					<input type="text" id="cubixsol-chat-table-search" placeholder="<?php esc_attr_e( 'Search customer, phone, product...', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?>" class="regular-text">
				</div>
			</div>

			<div class="cubixsol-chat-table-container">
				<table class="wp-list-table widefat striped cubixsol-chat-recovery-table" id="cubixsol-chat-leads-table">
					<thead>
						<tr>
							<th style="width:20%;"><?php esc_html_e( 'Customer Name & Email', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:14%;"><?php esc_html_e( 'WhatsApp Phone', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:24%;"><?php esc_html_e( 'Cart Items & Category', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:10%;"><?php esc_html_e( 'Cart Total', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:12%;"><?php esc_html_e( 'Status', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
							<th style="width:20%; text-align:right;"><?php esc_html_e( 'WhatsApp Action', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $cubixsol_chat_table_rows as $cubixsol_chat_row ) :
							$cubixsol_chat_is_recovered  = ( 'recovered' === $cubixsol_chat_row['status'] );
							$cubixsol_chat_status_filter = $cubixsol_chat_is_recovered ? 'recovered' : 'abandoned';
							$cubixsol_chat_clean_phone   = preg_replace( '/[^0-9]/', '', (string) $cubixsol_chat_row['phone'] );

							$cubixsol_chat_msg = str_replace(
								array( '{customer_name}', '{site_title}', '{cart_url}' ),
								array(
									$cubixsol_chat_row['name'],
									get_bloginfo( 'name' ),
									add_query_arg( 'coupon', 'SAVE10', function_exists( 'wc_get_cart_url' ) && wc_get_cart_url() ? wc_get_cart_url() : home_url( '/cart/' ) ),
								),
								$cubixsol_chat_recovery_template
							);

							$cubixsol_chat_wa_url = ! empty( $cubixsol_chat_clean_phone ) ? 'https://wa.me/' . $cubixsol_chat_clean_phone . '?text=' . rawurlencode( $cubixsol_chat_msg ) : '#';
							?>
						<tr class="cart-item-row" data-status="<?php echo esc_attr( $cubixsol_chat_status_filter ); ?>" data-cart-id="<?php echo esc_attr( $cubixsol_chat_row['id'] ); ?>">
							<td class="col-customer">
								<strong class="lead-name"><?php echo esc_html( $cubixsol_chat_row['name'] ); ?></strong><br>
								<span class="lead-email text-muted"><?php echo esc_html( $cubixsol_chat_row['email'] ? $cubixsol_chat_row['email'] : __( 'No email', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) ); ?></span><br>
								<small class="text-muted"><?php echo esc_html( $cubixsol_chat_row['time_ago'] ); ?></small>
							</td>
							<td class="col-phone">
								<?php if ( ! empty( $cubixsol_chat_row['phone'] ) ) : ?>
									<span class="cubixsol-chat-phone-pill lead-phone"><?php echo esc_html( $cubixsol_chat_row['phone'] ); ?></span>
								<?php else : ?>
									<span class="text-muted"><?php esc_html_e( 'No phone', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="col-items">
								<strong class="lead-item"><?php echo esc_html( wp_trim_words( $cubixsol_chat_row['items'], 8 ) ); ?></strong><br>
								<span class="cubixsol-chat-cat-tag lead-category"><?php echo esc_html( $cubixsol_chat_row['category'] ); ?></span>
							</td>
							<td class="col-total">
								<strong class="lead-total"><?php cubixsol_chat_admin_price( $cubixsol_chat_row['total'] ); ?></strong>
							</td>
							<td class="col-status">
								<?php if ( $cubixsol_chat_is_recovered ) : ?>
									<span class="cubixsol-chat-badge-recovered lead-status"><?php esc_html_e( 'Recovered ✓', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
								<?php else : ?>
									<span class="cubixsol-chat-badge-abandoned lead-status"><?php esc_html_e( 'Abandoned', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
								<?php endif; ?>
							</td>
							<td style="text-align:right;">
								<?php if ( $cubixsol_chat_is_recovered ) : ?>
									<span class="cubixsol-chat-recovered-check">✓ <?php esc_html_e( 'Paid Order', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
								<?php elseif ( ! empty( $cubixsol_chat_clean_phone ) ) : ?>
									<a href="<?php echo esc_url( $cubixsol_chat_wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary cubixsol-chat-wa-send-btn">
										<?php esc_html_e( '1-Click WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?>
									</a>
									<?php if ( ! empty( $cubixsol_chat_row['id'] ) && $cubixsol_chat_meta_api_ready ) : ?>
										<?php if ( ! empty( $cubixsol_chat_row['api_sent'] ) ) : ?>
											<span class="cubixsol-chat-api-sent-tag" title="<?php esc_attr_e( 'Recovery message already sent via Meta API', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?>"><?php esc_html_e( 'API ✓', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
										<?php else : ?>
											<button type="button" class="button button-secondary cubixsol-chat-api-send-btn" data-cart-id="<?php echo esc_attr( $cubixsol_chat_row['id'] ); ?>" title="<?php esc_attr_e( 'Send recovery message now via Meta Cloud API', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?>"><?php esc_html_e( 'Send via API', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></button>
										<?php endif; ?>
									<?php endif; ?>
									<?php if ( ! empty( $cubixsol_chat_row['id'] ) ) : ?>
										<button type="button" class="button button-secondary cubixsol-chat-mark-recovered-btn" data-cart-id="<?php echo esc_attr( $cubixsol_chat_row['id'] ); ?>" title="<?php esc_attr_e( 'Mark this cart as recovered', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?>">✓</button>
									<?php endif; ?>
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
