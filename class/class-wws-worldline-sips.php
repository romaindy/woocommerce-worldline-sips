<?php
/**
 * File for the Class WC_Worldline_SIPS used by WooCommerce as a Payment Gateway.
 *
 * @package WWS
 */

/**
 * Class WWS_Worldline_SIPS.
 */
class WWS_Worldline_SIPS extends WC_Payment_Gateway {

	/**
	 * Class constructor, set the WooCommerce payment gateway properties required.
	 *
	 * @return void
	 */
	public function __construct() {

		$this->id                 = 'worldline_sips';
		$this->has_fields         = true;
		$this->method_title       = __( 'Worldline SIPS 2.0', 'wws' );
		$this->method_description = __( 'Allows Worldline SIPS 2.0 payment (Sherlock, Merc@net, Sogenactif, Scellius Net, etc.).', 'wws' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->icon         = $this->get_option( 'icon' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions', $this->description );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Filters.
		add_filter( 'woocommerce_order_button_html', array( $this, 'remove_place_order_button_for_specific_payments' ) );
	}


	/**
	 * Initialize gateway settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = apply_filters(
			'wws_form_fields',
			array(
				'enabled'             => array(
					'title'   => __( 'Enable/Disable', 'wws' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Worldline SIPS 2.0', 'wws' ),
					'default' => 'yes',
				),
				'title'               => array(
					'title'       => __( 'Title', 'wws' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wws' ),
					'default'     => __( 'Credit card', 'wws' ),
					'desc_tip'    => true,
				),
				'icon'                => array(
					'title'       => __( 'Icon image URL', 'wws' ),
					'type'        => 'url',
					'description' => __( 'Add an icon next to the payment method on checkout.', 'wws' ),
					'default'     => '',
					'desc_tip'    => true,
				),
				'description'         => array(
					'title'       => __( 'Description', 'wws' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wws' ),
					'default'     => __( 'Pay with your credit card.', 'wws' ),
					'desc_tip'    => true,
				),
				'merchant_id'         => array(
					'title'       => __( 'Merchant ID', 'wws' ),
					'type'        => 'text',
					'description' => __( 'Your payment merchant ID provided by WorldLine.', 'wws' ),
					'desc_tip'    => true,
				),
				'secret_key'          => array(
					'title'       => __( 'Secret key', 'wws' ),
					'type'        => 'password',
					'description' => __( 'Your payment secret key provided by WorldLine.', 'wws' ),
					'desc_tip'    => true,
				),
				'key_version'         => array(
					'title' => __( 'Key version', 'wws' ),
					'type'  => 'number',
				),
				'environment'         => array(
					'title'   => __( 'Environment', 'wws' ),
					'type'    => 'select',
					'options' => array(
						'TEST' => 'Test',
						'SIMU' => 'Simulation',
						'PROD' => 'Production',
					),
				),
				'template_name'       => array(
					'title'       => __( 'Template name', 'wws' ),
					'type'        => 'text',
					'description' => __( 'Name of your template if you have customized your pages on WorldLine interface.', 'wws' ),
					'desc_tip'    => true,
					'default'     => 'default',
				),
				'iframe_width'        => array(
					'title'       => __( 'Iframe width', 'wws' ),
					'type'        => 'text',
					'description' => __( 'Width (in px) of the iframe where the payment will be displayed.', 'wws' ),
					'desc_tip'    => true,
					'default'     => '600',
				),
				'iframe_height'       => array(
					'title'       => __( 'Iframe height', 'wws' ),
					'type'        => 'text',
					'description' => __( 'Height (in px) of the iframe where the payment will be displayed.', 'wws' ),
					'desc_tip'    => true,
					'default'     => '530',
				),
				'auto_transaction_id' => array(
					'title'       => __( 'Transactions Reference ID', 'wws' ),
					'type'        => 'checkbox',
					'label'       => __( 'Is your transactions reference generated automatically?', 'wws' ),
					'description' => __( 'This settings is available on your Worldline interface. If you are unsure, leave this box unchecked.', 'wws' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
			)
		);
	}

	/**
	 * Include the template "payment.php".
	 *
	 * @return void
	 */
	public function payment_fields() {
		ob_start();
		include plugin_dir_path( __DIR__ ) . 'payment.php';
		$content = ob_get_contents();
		ob_end_clean();
		echo $content; // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Remove the default WooCommerce button "Pay".
	 *
	 * @param string $button HTML button.
	 *
	 * @return string
	 */
	public function remove_place_order_button_for_specific_payments( $button ) {
		$wws_order_button_text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$wws_style             = '';
		$button                = '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $wws_order_button_text ) . '" data-value="' . esc_attr( $wws_order_button_text ) . '" style="' . $wws_style . '">' . esc_html( $wws_order_button_text ) . '</button>';
		return $button;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		return array(
			'result'   => 'success',
			'redirect' => '#payment?timestamp=' . time(),
		);
	}
}
