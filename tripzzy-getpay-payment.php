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

define( 'GETPAY_PAYMENT_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', 'getpay_payment_init' );

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
