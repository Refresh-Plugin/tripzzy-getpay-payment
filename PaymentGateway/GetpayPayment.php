<?php
/**
 * Payment Gateway : Getpay Payment.
 *
 * @package tripzzy
 */

namespace Tripzzy\PaymentGateway;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Payment\PaymentGateways; // Base.
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Traits\GatewayTrait;
use Tripzzy\Core\Cart;
use Tripzzy\Core\Bookings;
use Tripzzy\Core\Forms\CheckoutForm;

if ( ! class_exists( 'Tripzzy\PaymentGateway\GetpayPayment' ) ) {
	/**
	 * Payment Gateway.
	 */
	class GetpayPayment extends PaymentGateways {
		use SingletonTrait;
		use GatewayTrait;

		/**
		 * Payment Gateway slug.
		 *
		 * @var   string
		 */
		protected static $payment_gateway = 'getpay_payment';

		/**
		 * Payment Gateway Title / name.
		 *
		 * @var   string
		 */
		protected static $payment_gateway_title;

		/**
		 * Tripzzy Settings.
		 *
		 * @var   array
		 */
		protected static $settings;

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$payment_gateway_title = 'Getpay Payment';
			self::$settings              = Settings::get();

			// Add Settings Fields.
			add_filter( 'tripzzy_filter_payment_gateways_args', array( $this, 'init_args' ) );

			// Gateway Script.
			add_filter( 'tripzzy_filter_gateway_scripts', array( $this, 'init_gateway_scripts' ) );

			// add it, if you need localized data for your gateway.
			add_filter( 'tripzzy_filter_localize_variables', array( __CLASS__, 'localized_variables' ) );
		}


		/**
		 * Payment gateway arguments.
		 */
		protected static function payment_gateway_args() {
			$args = array(
				'title'         => self::$payment_gateway_title,
				'name'          => self::$payment_gateway,
				'wrapper_class' => '',
				'fields'        => array(
					'enabled'       => array( // this key is for php side.
						'name'  => 'enabled', // this name and its key must be identical.
						'label' => __( 'Enabled' ),
						'value' => true,
					),
					'description'   => array(
						'name'  => 'description',
						'label' => __( 'Description' ),
						'type'  => 'textarea',
						'value' => __( 'Complete your booking by paying with Getpay Payment.' ),
					),
					// Getpay Payment Key.
					'pap_info'      => array(
						'name'  => 'pap_info',
						'type'  => 'text',
						'label' => __( 'Pap Info' ),
						'value' => '',
					),
					'ins_key'       => array(
						'name'  => 'ins_key',
						'type'  => 'text',
						'label' => __( 'Ins Key' ),
						'value' => '',
					),
					'opr_key'       => array(
						'name'  => 'opr_key',
						'type'  => 'text',
						'label' => __( 'Opr Key' ),
						'value' => '',
					),

					'test_pap_info' => array(
						'name'  => 'test_pap_info',
						'type'  => 'text',
						'label' => __( 'Test Pap Info' ),
						'value' => '',
					),
					'test_ins_key'  => array(
						'name'  => 'test_ins_key',
						'type'  => 'text',
						'label' => __( 'Test Ins Key' ),
						'value' => '',
					),
					'test_opr_key'  => array(
						'name'  => 'test_opr_key',
						'type'  => 'text',
						'label' => __( 'Test Opr Key' ),
						'value' => '',
					),
				),
			);
			return $args;
		}

		/**
		 * Gateway scripts arguments.
		 */
		protected static function gateway_scripts() {
			$data = self::geteway_data();
			$args = array();
			if ( ! empty( $data ) ) {

				$key_id    = $config['key_id'] ?? ''; // Payment key to use if required to pass in checkout js.
				$getpay_js = sprintf( '%sassets/getpay.js', GETPAY_PAYMENT_URL );
				$args[]    = $getpay_js;
			}
			return $args;
		}

		/**
		 * Localized variables for payment.
		 *
		 * @param array $localized All localized variables.
		 * @return array
		 */
		public static function localized_variables( $localized ) {

			$data = self::geteway_data();
			if ( ! empty( $data ) ) {
				$test_mode = $data['test_mode'];
				$config    = $data['config']; // Payment gateway configuration.

				$pap_info = $config['pap_info'] ?? '';
				$ins_key  = $config['ins_key'] ?? '';
				$opr_key  = $config['opr_key'] ?? '';
				if ( $test_mode ) {
					$pap_info = $config['test_pap_info'] ?? '';
					$ins_key  = $config['test_ins_key'] ?? '';
					$opr_key  = $config['test_opr_key'] ?? '';
				}

				$localized['gateway']['getpay_payment'] = array(
					'pap_info' => $pap_info,
					'ins_key'  => $ins_key,
					'opr_key'  => $opr_key,
				);
			}

			return $localized;
		}
	}
}
