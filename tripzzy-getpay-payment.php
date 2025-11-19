<?php
/**
 * Plugin Name: Tripzzy Getpay Payment
 * Plugin URI: https://wptripzzy.com
 * Description: Tripzzy Getpay allows you to checkout tripzzy with Getpay Global Payment integration.
 * Version: 1.0.0
 * Author: WP Tripzzy
 * Author URI: https://wptripzzy.com
 * Requires at least: 6.0
 * Requires Plugins: tripzzy
 * Requires PHP: 7.4
 * Tested up to: 6.8
 *
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Text Domain: payment-prototype
 * Domain Path: /languages/
 *
 * @package payment-prototype
 * @author  WP Tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\PaymentGateway\GetpayPayment;
use Tripzzy\Core\Seeder\PageSeeder;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;
define( 'TRIPZZY_GETPAY_PLUGIN_FILE', __FILE__ );
define( 'TRIPZZY_GETPAY_ABSPATH', dirname( TRIPZZY_GETPAY_PLUGIN_FILE ) . '/' );
define( 'TRIPZZY_GETPAY_PAYMENT_URL', plugin_dir_url( TRIPZZY_GETPAY_PLUGIN_FILE ) );

add_action( 'plugins_loaded', 'getpay_payment_init' );
add_filter(
	'tripzzy_filter_default_settings',
	function ( $default_settings ) {
		$default_settings['getpay_payment_page_id'] = '';
		$default_settings['getpay_success_page_id'] = '';
		$default_settings['getpay_failed_page_id']  = '';
		return $default_settings;
	}
);

/**
 * Additional Pages required for Getpay Payment.
 *
 * @return array
 */
function tripzzy_getpay_pages() {
	$pages = array(
		// Payment Page.
		array(
			'post_name'      => _x( 'getpay-payment', 'Page slug', 'tripzzy-getpay-payment' ),
			'post_title'     => _x( 'Payment', 'Page title', 'tripzzy-getpay-payment' ),
			'post_content'   => '<div id="checkout"></div>',
			'post_content_6' => '<!-- wp:html --><div id="checkout"></div><!-- /wp:html -->',
			'settings_key'   => 'getpay_payment_page_id',
			'title'          => __( 'Tz GetPay Payment', 'tripzzy-getpay-payment' ),
		),
		// Payment Success.
		array(
			'post_name'      => _x( 'payment-success', 'Page slug', 'tripzzy-getpay-payment' ),
			'post_title'     => _x( 'Payment Success', 'Page title', 'tripzzy-getpay-payment' ),
			'post_content'   => '[TRIPZZY_GETPAY_SUCCESS_PAGE]',
			'post_content_6' => '<!-- wp:shortcode -->[TRIPZZY_GETPAY_SUCCESS_PAGE]<!-- /wp:shortcode -->',
			'settings_key'   => 'getpay_success_page_id',
			'title'          => __( 'Tz GetPay Success', 'tripzzy-getpay-payment' ),
		),
		// Payment Failed.
		array(
			'post_name'      => _x( 'payment-failed', 'Page slug', 'tripzzy-getpay-payment' ),
			'post_title'     => _x( 'Payment Failed', 'Page title', 'tripzzy-getpay-payment' ),
			'post_content'   => '<div id="payment-failed"><h2>Payment Failed</h2><p>Unfortunately, your payment could not be processed. Please try again or contact support if the problem persists.</p></div>',
			'post_content_6' => '<!-- wp:html --><div id="payment-failed"><h2>Payment Failed</h2><p>Unfortunately, your payment could not be processed. Please try again or contact support if the problem persists.</p></div><!-- /wp:html -->',
			'settings_key'   => 'getpay_failed_page_id',
			'title'          => __( 'Tz GetPay Failed', 'tripzzy-getpay-payment' ),
		),
	);
	return $pages;
}
register_activation_hook(
	__FILE__,
	function () {
		$settings = Settings::get();

		$pages = tripzzy_getpay_pages();
		foreach ( $pages as $page_data ) {

			$settings_key = $page_data['settings_key'];
			$page_id      = isset( $settings[ $settings_key ] ) ? $settings[ $settings_key ] : 0;

			$new_page_id = PageSeeder::create( $page_data, $page_id ); // only return page id if post is created.
			if ( $new_page_id > 0 ) {
				$settings[ $settings_key ] = $new_page_id;
				MetaHelpers::update_post_meta( $new_page_id, 'settings_key', $settings_key ); // To check Tripzzy created pages later.
				Settings::update( $settings );
			}
		}
	}
);

/**
 * Init Getpay Payment.
 *
 * @return void
 */
function getpay_payment_init() {
	if ( ! class_exists( 'Tripzzy' ) ) {
		return;
	}
	require_once 'PaymentGateway/GetpayPayment.php';
	require_once 'PaymentGateway/GetpayFunctions.php';

	add_action(
		'init',
		function () {
			new GetpayPayment();
		}
	);
}
