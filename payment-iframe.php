<?php
/**
 * Payment iFrame.
 *
 * @package WWS

 * phpcs:disable WordPress.Security.NonceVerification.Missing
 */

use Worldline\Sips\Common\Field\PaypageData;
use Worldline\Sips\Common\SipsEnvironment;
use Worldline\Sips\Paypage\PaypageRequest;
use Worldline\Sips\SipsClient;

require 'vendor/autoload.php';
require_once '../../../wp-load.php';
require WWS_PLUGIN_PATH . '/assets/functions/get-response-code-message.php';

wp_head();

global $woocommerce;
$wws_options  = get_option( 'woocommerce_worldline_sips_settings' );
$wws_order_id = $woocommerce->session->order_awaiting_payment;
if ( ! empty( $wws_order_id ) ) {
	$wws_order = wc_get_order( (int) $wws_order_id );
}

if ( empty( $wws_options ) ) {
	esc_html_e( 'Worldline SIPS for WooCommerce hasn\'t been configured yet.', 'wws' );
} elseif ( ! empty( $_POST['Data'] ) ) {

	$wws_raw_data = explode( '|', $_POST['Data'] );
	$wws_data     = array();
	foreach ( $wws_raw_data as $wws_element ) {
		$wws_element                 = explode( '=', $wws_element );
		$wws_data[ $wws_element[0] ] = $wws_element[1];
	}

	$wws_order = wc_get_order( (int) $wws_data['orderId'] );

	if ( '00' === $wws_data['responseCode'] ) {
		// Mark payment as completed.
		$wws_order->payment_complete();

		// Save transaction reference.
		$wws_order->add_meta_data( 'transaction_reference', $wws_data['transactionReference'], true );
		$wws_order->save();

		// Reduce stock levels.
		wc_reduce_stock_levels( $wws_order_id );

		// Remove cart.
		WC()->cart->empty_cart();

		?>
		<script>
			(function () {
				window.parent.location = '<?php echo esc_html( $wws_order->get_checkout_order_received_url() ); ?>';
			})();
		</script>
		<?php
	} elseif ( '17' === $wws_data['responseCode'] ) {
		// Cancel order.
		$wws_order->update_status( 'cancelled', 'Cancelled by user.' );
		if ( WC()->session ) {
			WC()->session->set( 'order_awaiting_payment', false );
		}

		// Empty cart.
		WC()->cart->empty_cart( true );

		?>
		<script>
			(function () {
				window.parent.location = '<?php echo esc_html( $wws_order->get_cancel_order_url() ); ?>';
			})();
		</script>
		<?php
	} else {
		echo esc_html( wws_get_response_code_message( $wws_data['responseCode'] ) );
	}
} elseif ( ! empty( $wws_order ) && 'cancelled' !== $wws_order->get_status() ) {
	try {
		$wws_environment         = $wws_options['environment'] ?? 'SIMU';
		$wws_merchant_id         = $wws_options['merchant_id'];
		$wws_secret_key          = $wws_options['secret_key'];
		$wws_key_version         = $wws_options['key_version'];
		$wws_template_name       = $wws_options['template_name'];
		$wws_auto_transaction_id = 'yes' === $wws_options['auto_transaction_id'];

		// Override credentials if it's simulation mode.
		if ( 'SIMU' === $wws_environment ) {
			$wws_merchant_id = '002001000000001';
			$wws_secret_key  = '002001000000001_KEY1';
			$wws_key_version = 1;
		}

		$wws_sips_client = new SipsClient(
			new SipsEnvironment( $wws_environment ),
			$wws_merchant_id,
			$wws_secret_key,
			$wws_key_version
		);

		$wws_paypage_request = new PaypageRequest();
		$wws_paypage_request->setTemplateName( $wws_template_name );
		$wws_paypage_request->setAmount( (float) WC()->cart->get_total( 'wws' ) * 100 );
		$wws_paypage_request->setCurrencyCode( 'EUR' );
		$wws_paypage_request->setNormalReturnUrl( WWS_PLUGIN_URL . '/payment-iframe.php' );
		$wws_paypage_request->setOrderChannel( 'INTERNET' );
		$wws_paypage_request->setTransactionReference( ( 'SIMU' === $wws_environment || ! $wws_auto_transaction_id ) ? 'WWS' . wp_rand() : '' );
		$wws_paypage_request->setOrderId( $woocommerce->session->order_awaiting_payment );
		$wws_paypage_data = new PaypageData();
		$wws_paypage_data->setBypassReceiptPage( true );
		$wws_paypage_request->setPaypageData( $wws_paypage_data );
		$wws_initialization_response = $wws_sips_client->initialize( $wws_paypage_request );

		if ( ! empty( $wws_initialization_response->getRedirectionUrl() ) ) {
			?>
			<form method="POST"
					action="<?php echo esc_attr( $wws_initialization_response->getRedirectionUrl() ); ?>"
					id="worldline-sips-form">
				<input type="hidden" name="redirectionVersion"
						value="<?php echo esc_attr( $wws_initialization_response->getRedirectionVersion() ); ?>">
				<input type="hidden" name="redirectionData"
						value="<?php echo esc_attr( $wws_initialization_response->getRedirectionData() ); ?>">
				<input type="submit" value="Continue" class="button" style="visibility: hidden;">
			</form>
			<script>
				(function () {
					// Submit Worldline information and unblock UI.
					jQuery('#worldline-sips-form').submit();
					window.parent.jQuery('#place_order').hide();
					window.parent.jQuery('#worldline-sips-woocommerce-iframe').show();
					window.parent.jQuery('form.checkout').removeClass('processing').unblock();
					window.parent.document.getElementById('worldline-sips-woocommerce-iframe').setAttribute('height', '<?php echo esc_attr( $wws_options['iframe_height'] ?? 530 ); ?>');
				})();
			</script>
			<?php
		} else {
			esc_html_e( 'Error: an error occurred. Please check your Merchant ID, your private key and your key version.', 'wws' );
			?>
			<br/>
			<strong><?php esc_html_e( 'Technical details:', 'wws' ); ?></strong><br/>
			<?php
			echo esc_html( $wws_initialization_response->getRedirectionStatusMessage() );
		}
	} catch ( Exception $error ) {
		esc_html_e( 'Error: an error occurred. Please check your Merchant ID, your private key and your key version.', 'wws' );
		?>
		<br/>
		<strong><?php esc_html_e( 'Technical details:', 'wws' ); ?></strong><br/>
		<?php
		echo esc_html( $error->getMessage() );
	}
} else {
	?>
	<script>
		(function () {
			window.parent.onhashchange = function () {
				window.location.reload();
			};
		})();
	</script>
	<a onclick="window.location.reload()"
		class="button"><?php esc_html_e( 'Click here to continue', 'wws' ); ?></a>
	<?php
}

wp_footer();