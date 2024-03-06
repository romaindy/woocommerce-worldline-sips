<?php
/**
 * The template used to display the iFrame.
 *
 * @package WWS
 * @since 1.0.0
 */

$wws_options  = get_option( 'woocommerce_worldline_sips_settings' );
$wws_order_id = WC()->session->get( 'order_awaiting_payment' );
?>
<iframe id="worldline-sips-woocommerce-iframe"
		src="<?php echo esc_attr( WWS_PLUGIN_URL . '/payment-iframe.php' ); ?>"
		height="<?php echo esc_attr( $wws_options['iframe_height'] ?? 600 ); ?>"
		width="<?php echo esc_attr( $wws_options['iframe_width'] ?? 600 ); ?>"
		target="_parent"
		style="max-width: 100%;">You need an iframe capable browser to view this content.
</iframe>
