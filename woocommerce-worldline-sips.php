<?php
/**
 * Plugin Name: WooCommerce Worldline Sips
 * Plugin URI: https://www.worldline-sips-woocommerce.com/
 * Description: Passerelle de paiement pour Worldline Sips 2.0 (Sherlock, Mercanet, Sogenactif, Scellius Net).
 * Version: 1.1.0
 * Author: 21 Pixels
 * Author URI: https://www.21pixels.studio/
 *
 * @package WWS
 *
 * phpcs:disable WordPress.Security.NonceVerification.Missing
 */

defined( 'ABSPATH' ) || exit;

// This plugin needs WooCommerce to work.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	die;
}

define( 'WWS_VERSION', '1.1.0' );
define( 'WWS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WWS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WWS_OFFICIAL_WEBSITE', 'https://www.worldline-sips-woocommerce.com/' );

/**
 * Enqueue styles and scripts.
 *
 * @return void
 */
function wws_enqueue_scripts() {
	wp_enqueue_script( 'wws_scripts', WWS_PLUGIN_URL . '/dist/scripts.min.js', array( 'jquery' ), WWS_VERSION, true );
	wp_enqueue_style( 'wws_style', WWS_PLUGIN_URL . '/dist/style.min.css', false, WWS_VERSION );
}

add_action( 'wp_enqueue_scripts', 'wws_enqueue_scripts' );

/**
 * Add the translations.
 *
 * @return void
 */
function wws_localisation() {
	load_plugin_textdomain( 'wws', false, dirname( plugin_basename( __FILE__ ) ) . '/assets/translations/' );
}

add_action( 'plugins_loaded', 'wws_localisation', 10, 0 );


/**
 * Add a new WooCommerce Payment Gateway.
 *
 * @param array $gateways Array of WooCommerce gateways.
 * @return array
 */
function wws_add_to_gateways( $gateways ) {
	$gateways[] = 'WWS_Worldline_SIPS';
	return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'wws_add_to_gateways' );


/**
 * Adds plugin page links.
 *
 * @param array $links All plugin links.
 *
 * @return array $links All plugin links + our custom links (i.e., "Settings").
 */
function wws_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wws' ) . '">' . __( 'Configure', 'wws' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wws_plugin_links' );

/**
 * Init the plugin.
 *
 * @return void
 */
function wws_offline_gateway_init() {
	require 'class/class-wc-worldline-sips.php';
}

add_action( 'plugins_loaded', 'wws_offline_gateway_init', 11 );

/**
 * Function to display a notice on top of the dashboard on WordPress administration.
 *
 * @param string $notice The message to display.
 * @param string $type Type of the message (success or error).
 * @param bool   $display_form Display the renewal form.
 *
 * @return false
 */
function wws_display_notice( $notice, $type = 'error', $display_form = true ) {
	?>
	<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
		<p>
			<?php echo esc_html( $notice ); ?>
			<?php if ( $display_form ) : ?>
				<a href="<?php echo esc_attr( WWS_OFFICIAL_WEBSITE ); ?>produit/renouvellement-de-licence/"><?php esc_html_e( 'Renew my licence', 'wws' ); ?></a>
			<?php endif; ?>
		</p>

		<?php if ( $display_form ) : ?>
			<form method="post" action="#">
				<p>
					<input type="text" name="wws_licence" placeholder="<?php esc_attr_e( 'Your licence key', 'wws' ); ?>" aria-label="<?php esc_attr_e( 'Your licence key', 'wws' ); ?>"/>
					<input type="submit" class="button button-primary">
				</p>
			</form>
		<?php endif; ?>
	</div>
	<?php
	return false;
}

/**
 * Display a notice after the user enter a licence key.
 *
 * @return void
 */
function wws_update_licence() {
	if ( ! empty( $_POST['wws_licence'] ) ) {
		if ( wp_is_uuid( $_POST['wws_licence'] ) && wws_check_licence( $_POST['wws_licence'] ) ) {
			file_put_contents( WWS_PLUGIN_PATH . '/licence.txt', $_POST['wws_licence'] );
			wws_display_notice( __( 'Thank you. Your licence has been updated.', 'wws' ), 'success', false );
		} else {
			wws_display_notice( __( 'Licence not valid.', 'wws' ) );
		}
	}
}

add_action( 'init', 'wws_update_licence' );

/**
 * Check if the licence is valid.
 *
 * @return void | false
 */
function wws_display_licence_notice() {
	if ( ! empty( $_POST['wws_licence'] ) ) {
		return;
	}

	$licence_key        = file_get_contents( WWS_PLUGIN_PATH . '/licence.txt' );
	$next_licence_check = new DateTime( 'now -7 days' );
	if ( empty( $licence_key ) ) {
		return wws_display_notice( __( 'Licence key for Worldline SIPS for WooCommerce is missing.', 'wws' ) );
	}

	if ( get_option( 'wws_last_licence_check', true ) > $next_licence_check->format( 'Y-m-d H:i:s' ) ) {
		return;
	}

	if ( ! wws_check_licence( $licence_key ) ) {
		return wws_display_notice( __( 'Your licence key for Worldline SIPS for WooCommerce has expired.', 'wws' ) );
	}

	update_option( 'wws_last_licence_check', gmdate( 'd-m-Y H:i:s' ) );
	return;
}

add_action( 'admin_notices', 'wws_display_licence_notice' );

/**
 * Check if the licence is expired.
 *
 * @param string $licence_key Licence key.
 * @return bool
 */
function wws_check_licence( $licence_key ) {
	$now = gmdate( 'Y-m-d H:i:s' );
	$ch  = curl_init();
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_URL, WWS_OFFICIAL_WEBSITE . 'wp-json/licence/v1/licence/' . $licence_key );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	$data = json_decode( curl_exec( $ch ) );
	curl_close( $ch );

	return ! empty( $data->expire ) && $data->expire > $now;
}

/**
 * Check if the current version of the plugin is outdated.
 *
 * @return bool
 */
function wws_is_current_version_up_to_date() {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_URL, WWS_OFFICIAL_WEBSITE . 'wp-json/licence/v1/version/' );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	$data = json_decode( curl_exec( $ch ) );
	curl_close( $ch );

	return ! empty( $data->data ) && ! empty( $data->data->version ) && WWS_VERSION === $data->data->version;
}

/**
 * Display a notice if the plugin is outdated.
 *
 * @return false|void
 */
function wws_update_plugin() {
	$next_version_check = new DateTime( 'now -7 days' );

	if ( get_option( 'wws_last_version_check', true ) > $next_version_check->format( 'Y-m-d H:i:s' ) ) {
		return;
	}

	if ( ! wws_is_current_version_up_to_date() ) {
		$licence_key = file_get_contents( WWS_PLUGIN_PATH . '/licence.txt' );
		$url         = WWS_OFFICIAL_WEBSITE . 'download.php?licence_key=' . $licence_key;
		return wws_display_notice( __( 'Worldline SIPS for WooCommerce is outdated. Please download the new version here:', 'wws' ) . ' <a href="' . $url . '" target="_blank">' . $url . '</a>', 'error', false );
	}
	update_option( 'wws_last_version_check', gmdate( 'd-m-Y H:i:s' ) );
}

add_action( 'init', 'wws_update_plugin' );
