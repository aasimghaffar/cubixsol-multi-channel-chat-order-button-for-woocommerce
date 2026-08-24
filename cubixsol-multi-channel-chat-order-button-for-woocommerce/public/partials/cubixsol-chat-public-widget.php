<?php
/**
 * Public Floating WhatsApp Widget Partial View.
 *
 * @package    Cubixsol_Chat
 * @subpackage Cubixsol_Chat/public/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="cubixsol-chat-widget-container" class="cubixsol-chat-pos-<?php echo esc_attr( $position ); ?>" style="--cubixsol-chat-color: <?php echo esc_attr( $theme_color ); ?>;">
	<!-- Floating Pill Button -->
	<button id="cubixsol-chat-trigger-btn" class="cubixsol-chat-floating-btn" aria-label="<?php echo esc_attr( $cta_text ); ?>" aria-expanded="false" aria-controls="cubixsol-chat-popup">
		<svg class="cubixsol-chat-bubble-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
		<span><?php echo esc_html( $cta_text ); ?></span>
	</button>

	<!-- Popover Multi-Agent Card -->
	<div id="cubixsol-chat-popup" class="cubixsol-chat-popup-card cubixsol-chat-hidden">
		<div class="cubixsol-chat-popup-header">
			<div>
				<h4 class="cubixsol-chat-header-title"><?php echo esc_html( $header_title ); ?></h4>
				<p class="cubixsol-chat-header-subtitle"><?php echo esc_html( $header_sub ); ?></p>
			</div>
			<button id="cubixsol-chat-close-btn" class="cubixsol-chat-close-btn" aria-label="<?php esc_attr_e( 'Close', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?>">&times;</button>
		</div>

		<div class="cubixsol-chat-greeting-box">
			<p><?php echo esc_html( $greeting ); ?></p>
		</div>

		<div class="cubixsol-chat-agents-list">
			<?php if ( ! empty( $agents ) ) : ?>
				<?php
				foreach ( $agents as $cubixsol_chat_agent ) :
					$cubixsol_chat_clean_phone = preg_replace( '/[^0-9]/', '', (string) ( $cubixsol_chat_agent['phone'] ?? '' ) );
					$cubixsol_chat_channel     = $cubixsol_chat_agent['channel'] ?? 'whatsapp';
					$cubixsol_chat_is_online   = $this->is_agent_online( $cubixsol_chat_agent );
					$cubixsol_chat_status_text = $cubixsol_chat_is_online ? __( 'Online', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ) : __( 'Away', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
					$cubixsol_chat_status_cls  = $cubixsol_chat_is_online ? 'cubixsol-chat-status-online' : 'cubixsol-chat-status-offline';

					if ( 'telegram' === $cubixsol_chat_channel ) {
						$cubixsol_chat_target_url = 'https://t.me/' . ltrim( $cubixsol_chat_agent['phone'], '@' );
						$cubixsol_chat_badge_name = __( 'Telegram', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
					} elseif ( 'phone' === $cubixsol_chat_channel ) {
						$cubixsol_chat_target_url = 'tel:' . $cubixsol_chat_agent['phone'];
						$cubixsol_chat_badge_name = __( 'Call', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
					} else {
						$cubixsol_chat_target_url = 'https://wa.me/' . $cubixsol_chat_clean_phone;
						$cubixsol_chat_badge_name = __( 'WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' );
					}
					?>
				<a href="<?php echo esc_url( $cubixsol_chat_target_url ); ?>" target="_blank" rel="noopener noreferrer" class="cubixsol-chat-agent-row cubixsol-chat-track-click" data-agent="<?php echo esc_attr( $cubixsol_chat_agent['name'] ); ?>">
					<div class="cubixsol-chat-agent-avatar">
						<span><?php echo esc_html( mb_substr( $cubixsol_chat_agent['name'], 0, 1 ) ); ?></span>
						<span class="cubixsol-chat-status-indicator <?php echo esc_attr( $cubixsol_chat_status_cls ); ?>" title="<?php echo esc_attr( $cubixsol_chat_status_text ); ?>"></span>
					</div>
					<div class="cubixsol-chat-agent-details">
						<span class="cubixsol-chat-agent-name"><?php echo esc_html( $cubixsol_chat_agent['name'] ); ?></span>
						<span class="cubixsol-chat-agent-role"><?php echo esc_html( $cubixsol_chat_agent['role'] ); ?> &bull; <small><?php echo esc_html( $cubixsol_chat_status_text ); ?></small></span>
					</div>
					<span class="cubixsol-chat-channel-badge"><?php echo esc_html( $cubixsol_chat_badge_name ); ?></span>
				</a>
				<?php endforeach; ?>
			<?php elseif ( ! empty( $default_phone ) ) : ?>
				<?php $cubixsol_chat_default_clean = preg_replace( '/[^0-9]/', '', (string) $default_phone ); ?>
				<a href="<?php echo esc_url( 'https://wa.me/' . $cubixsol_chat_default_clean ); ?>" target="_blank" rel="noopener noreferrer" class="cubixsol-chat-agent-row cubixsol-chat-track-click" data-agent="Default">
					<div class="cubixsol-chat-agent-avatar"><span>W</span><span class="cubixsol-chat-status-indicator cubixsol-chat-status-online"></span></div>
					<div class="cubixsol-chat-agent-details">
						<span class="cubixsol-chat-agent-name"><?php echo esc_html( $header_title ); ?></span>
						<span class="cubixsol-chat-agent-role"><?php esc_html_e( 'Chat with us on WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
					</div>
					<span class="cubixsol-chat-channel-badge"><?php esc_html_e( 'WhatsApp', 'cubixsol-multi-channel-chat-order-button-for-woocommerce' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>
