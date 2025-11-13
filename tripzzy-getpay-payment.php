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

define( 'GETPAY_PAYMENT_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', 'getpay_payment_init' );
add_filter(
	'tripzzy_filter_default_settings',
	function ( $default_settings ) {
		$default_settings['payment_page_id']    = '';
		$default_settings['processing_page_id'] = '';
		return $default_settings;
	}
);

function tripzzy_getpay_pages() {
	$pages = array(
		// Payment Page
		array(
			'post_name'      => _x( 'getpay-payment', 'Page slug', 'tripzzy-getpay-payment' ),
			'post_title'     => _x( 'Payment', 'Page title', 'tripzzy-getpay-payment' ),
			'post_content'   => '<div id="checkout"></div>',
			'post_content_6' => '<!-- wp:html --><div id="checkout"></div><!-- /wp:html -->',
			'settings_key'   => 'payment_page_id',
			'title'          => __( 'GetPay Payment Page', 'tripzzy-getpay-payment' ),
		),
		// Payment Success.
		array(
			'post_name'      => _x( 'processing-booking', 'Page slug', 'tripzzy-getpay-payment' ),
			'post_title'     => _x( 'Processing', 'Page title', 'tripzzy-getpay-payment' ),
			'post_content'   => '<div id="checkout"></div>',
			'post_content_6' => '<!-- wp:html --><div id="checkout"></div><!-- /wp:html -->',
			'settings_key'   => 'processing_page_id',
			'title'          => __( 'GetPay Thank You/Processing Page', 'tripzzy-getpay-payment' ),
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

	add_action(
		'init',
		function () {
			new GetpayPayment();
		}
	);
}
