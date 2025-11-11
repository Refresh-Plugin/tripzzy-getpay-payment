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
use Tripzzy\Core\Helpers\Page;
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
		 * Assets path.
		 *
		 * @var string
		 */
		private static $assets_url;

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$payment_gateway_title = 'Getpay Payment';
			self::$settings              = Settings::get();
			self::$assets_url            = sprintf( '%sassets/', GETPAY_PAYMENT_URL );
			// Add Settings Fields.
			add_filter( 'tripzzy_filter_payment_gateways_args', array( $this, 'init_args' ) );

			// Frontend.
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ), 100 );

			// add it, if you need localized data for your gateway.
			add_filter( 'tripzzy_filter_localize_variables', array( $this, 'localized_variables' ) );

			// Add Checkout div in checkout page.
			add_action( 'tripzzy_checkout_after_submit_button', array( $this, 'add_checkout_div' ) );

			add_shortcode(
				'TRIPZZY_PAYMENT',
				function () {
					ob_start();
					wp_enqueue_script( 'tripzzy-getpay-bundle' );
					?>
					<div id="checkout"></div>
					<?php
					$content = ob_get_contents();
					ob_end_clean();
					return $content;
				}
			);
		}

		/**
		 * Payment gateway arguments.
		 *
		 * @internal Admin side.
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
		 * Tripzzy Getpay Frontend Script.
		 *
		 * @return void
		 */
		public function frontend_scripts() {
			$settings = Settings::get();
			wp_register_script( 'tripzzy-getpay-bundle', 'https://minio.finpos.global/getpay-cdn/webcheckout/v5/bundle.js', array(), '1.0.0', true );
			if ( Page::is( 'checkout' ) ) {
				wp_enqueue_script( 'tripzzy-getpay-custom', self::$assets_url . 'getpay.js', array( 'tripzzy-getpay-bundle' ), '1.0.0', true );
				wp_enqueue_style( 'tripzzy-getpay-custom', self::$assets_url . 'getpay.css', array(), '1.0.0' );
			}
		}

		/**
		 * Add div in Tripzzy Checkout page with id checkout.
		 *
		 * @return void
		 */
		public function add_checkout_div() {
			?>
			<div id="checkout" hidden></div>
			<?php
		}

		/**
		 * Localized variables for payment.
		 *
		 * @param array $localized All localized variables.
		 * @return array
		 */
		public function localized_variables( $localized ) {

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

				$localized['gateway']['getpay_payment']['thankyou_page_url'] = Page::get_url( 'thankyou' );
				// $thankyou_page_url = add_query_arg( 'tripzzy_key', $data['tripzzy_nonce'], $thankyou_page_url );
				// $thankyou_page_url = add_query_arg( 'booking_id', $booking_id, $thankyou_page_url );
				// $thankyou_page_url = apply_filters( 'tripzzy_filter_thankyou_page_url', $thankyou_page_url );
			}

			return $localized;
		}
	}
}
