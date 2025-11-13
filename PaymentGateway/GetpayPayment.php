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
			self::$assets_url            = sprintf( '%sassets/', TRIPZZY_GETPAY_PAYMENT_URL );
			// Add Settings Fields.
			add_filter( 'tripzzy_filter_payment_gateways_args', array( $this, 'init_args' ) );

			// Add Seeder Field To list Page in settings.
			add_filter( 'tripzzy_filter_page_seeder', array( $this, 'add_page_in_settings' ) );

			// Frontend.
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ), 100 );

			// add it, if you need localized data for your gateway.
			add_filter( 'tripzzy_filter_localize_variables', array( $this, 'localized_variables' ) );

			// Add Checkout div in checkout page.
			add_action( 'tripzzy_checkout_after_submit_button', array( $this, 'add_checkout_div' ) );

			// Page Template.
			add_filter( 'template_include', array( $this, 'template_include' ) );
			// Add Query vers for processing/success page.
			add_filter( 'query_vars', array( $this, 'query_vars' ) );

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
					'enabled'            => array( // this key is for php side.
						'name'  => 'enabled', // this name and its key must be identical.
						'label' => __( 'Enabled' ),
						'value' => true,
					),
					'description'        => array(
						'name'  => 'description',
						'label' => __( 'Description' ),
						'type'  => 'textarea',
						'value' => __( 'Complete your booking by paying with Getpay Payment.' ),
					),
					// Getpay Payment Key.
					'business_name'      => array(
						'name'        => 'business_name',
						'type'        => 'text',
						'label'       => __( 'Business Name' ),
						'value'       => '',
						'placeholder' => 'MAYA TRIPS PVT LTD',
						'tooltip'     => 'Your Business Name',
					),
					'website_domain'     => array(
						'name'        => 'website_domain',
						'type'        => 'text',
						'label'       => __( 'Website Domain' ),
						'value'       => '',
						'placeholder' => 'https://your-site.com',
						'tooltip'     => 'Your Business Website URL',
					),
					'live_credentials'   => array(
						'name'    => 'live_credentials',
						'type'    => 'heading',
						'label'   => __( 'Live Credentials' ),
						'tooltip' => 'Add Your Live Credentials for real Transaction.',
					),
					'pap_info'           => array(
						'name'    => 'pap_info',
						'type'    => 'text',
						'label'   => __( 'Pap Info' ),
						'value'   => '',
						'tooltip' => 'Pap Info Provided by GetPay.',
					),
					'opr_key'            => array(
						'name'    => 'opr_key',
						'type'    => 'text',
						'label'   => __( 'Opr Key' ),
						'value'   => '',
						'tooltip' => 'Opr Key Provided by Getpay.',
					),
					'ins_key'            => array(
						'name'    => 'ins_key',
						'type'    => 'text',
						'label'   => __( 'Ins Key' ),
						'value'   => '',
						'tooltip' => 'Ins Key Provided by Getpay. Leave empty.',
					),

					'bundle_js_url'      => array(
						'name'        => 'bundle_js_url',
						'type'        => 'text',
						'label'       => __( 'Bundle JS URL' ),
						'value'       => '',
						'placeholder' => 'https://minio.finpos.global/.../bundle.min.js',
						'tooltip'     => 'Bundle URL Provided by GetPay.',
					),
					'base_url'           => array(
						'name'        => 'base_url',
						'type'        => 'text',
						'label'       => __( 'Base URL' ),
						'value'       => '',
						'placeholder' => 'https://uat-bank-getpay.nchl.com.np/...',
						'tooltip'     => 'Base URL Provided by GetPay.',
					),
					'test_credentials'   => array(
						'name'    => 'test_credentials',
						'type'    => 'heading',
						'label'   => __( 'Test Credentials' ),
						'tooltip' => 'Add Your Test Credentials for testing purpose.',
					),
					'test_pap_info'      => array(
						'name'    => 'test_pap_info',
						'type'    => 'text',
						'label'   => __( 'Test Pap Info' ),
						'value'   => '',
						'tooltip' => 'Pap Info Provided by GetPay.',
					),
					'test_opr_key'       => array(
						'name'    => 'test_opr_key',
						'type'    => 'text',
						'label'   => __( 'Test Opr Key' ),
						'value'   => '',
						'tooltip' => 'Opr Key Provided by Getpay.',
					),
					'test_ins_key'       => array(
						'name'    => 'test_ins_key',
						'type'    => 'text',
						'label'   => __( 'Test Ins Key' ),
						'value'   => '',
						'tooltip' => 'Ins Key Provided by Getpay. Leave empty.',
					),
					'test_bundle_js_url' => array(
						'name'        => 'test_bundle_js_url',
						'type'        => 'text',
						'label'       => __( 'Test Bundle JS URL' ),
						'value'       => '',
						'placeholder' => 'https://minio.finpos.global/.../bundle.min.js',
						'tooltip'     => 'Bundle URL Provided by GetPay.',
					),
					'test_base_url'      => array(
						'name'        => 'test_base_url',
						'type'        => 'text',
						'label'       => __( 'Test Base URL' ),
						'value'       => '',
						'placeholder' => 'https://uat-bank-getpay.nchl.com.np/...',
						'tooltip'     => 'Test Base URL Provided by GetPay.',
					),
				),
			);
			return $args;
		}

		public function add_page_in_settings( $pages ) {
			$getpay_pages = tripzzy_getpay_pages();
			if ( ! empty( $getpay_pages ) ) {
				$pages = array_merge( $pages, $getpay_pages );
			}
			return $pages;
		}

		/**
		 * Tripzzy Getpay Frontend Script.
		 *
		 * @return void
		 */
		public function frontend_scripts() {
			$settings        = Settings::get();
			$data            = self::geteway_data();
			$current_page_id = get_the_id();
			if ( ! $current_page_id ) {
				return;
			}
			if ( ! empty( $data ) ) {
				$test_mode     = $data['test_mode'];
				$config        = $data['config']; // Payment gateway configuration.
				$bundle_js_url = $config['bundle_js_url'] ?? '';
				if ( $test_mode ) {
					$bundle_js_url = $config['test_bundle_js_url'] ?? '';
				}

				wp_register_script( 'tripzzy-getpay-bundle', $bundle_js_url, array(), '1.0.0', true );
				wp_register_script( 'tripzzy-getpay-local-storage', self::$assets_url . 'local-storage.js', array(), '1.0.0', true );
				if ( Page::is( 'checkout' ) ) {
					wp_enqueue_script( 'tripzzy-getpay-custom', self::$assets_url . 'getpay.js', array( 'tripzzy-getpay-bundle', 'tripzzy-getpay-local-storage' ), '1.0.0', true );
					wp_enqueue_style( 'tripzzy-getpay-custom', self::$assets_url . 'getpay.css', array(), '1.0.0' );
				}

				$payment_page_id    = (int) $settings['payment_page_id'] ?? 0;
				$processing_page_id = (int) $settings['processing_page_id'] ?? 0;
				if ( $payment_page_id === $current_page_id ) {
					wp_enqueue_script( 'tripzzy-getpay-bundle' );
				}
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
		 * Add Template For Page.
		 *
		 * @return void
		 */
		public function template_include( $template ) {
			if ( $this->is_page( 'processing' ) ) {
				$page_template = $this->get_template_file( 'processing.php' );
				if ( $page_template ) {
					return $page_template;
				}
			}
			return $template;
		}

		public function get_template_file( $template_name, $args = array() ) {
			$template_path = apply_filters( 'tripzzy_filter_getpay_template_path', 'tripzzy-getpay-payment/' );
			$default_path  = sprintf( '%1$stemplates/', TRIPZZY_GETPAY_ABSPATH );

			// Look templates in theme first.
			$template = locate_template(
				array(
					trailingslashit( $template_path ) . $template_name,
					$template_name,
				),
				false,
				true,
				$args
			);

			if ( ! $template ) { // Load from the plugin if the file is not in the theme.
				$template = $default_path . $template_name;
			}
			if ( file_exists( $template ) ) {
				return $template;
			}
			return false;
		}

		public function query_vars( $qvars ) {
			$qvars[] = 'token';
			return $qvars;
		}

		/**
		 * To check getpay page.
		 *
		 * @param string $slug Page slug to check.
		 */
		public function is_page( $slug = '' ) {
			if ( ! $slug ) {
				return;
			}
			$settings        = self::$settings;
			$current_page_id = get_the_ID();
			if ( ! $current_page_id ) {
				return;
			}

			switch ( $slug ) {
				case 'payment':
					$payment_page_id = (int) $settings['payment_page_id'] ?? 0;
					return $payment_page_id && $payment_page_id === $current_page_id;
				case 'processing':
					$processing_page_id = (int) $settings['processing_page_id'] ?? 0;
					return $processing_page_id && $processing_page_id === $current_page_id;
			}
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
				$settings           = self::$settings;
				$test_mode          = $data['test_mode'];
				$config             = $data['config']; // Payment gateway configuration.
				$payment_page_id    = $settings['payment_page_id'] ?? 0;
				$processing_page_id = $settings['processing_page_id'] ?? 0;

				// General.
				$business_name  = $config['business_name'] ?? 'Tripzzy';
				$website_domain = $config['website_domain'] ?? 'Tripzzy';

				// Specific.
				$pap_info      = $config['pap_info'] ?? '';
				$opr_key       = $config['opr_key'] ?? '';
				$ins_key       = $config['ins_key'] ?? '';
				$bundle_js_url = $config['bundle_js_url'] ?? '';
				$base_url      = $config['base_url'] ?? '';
				if ( $test_mode ) {
					$pap_info      = $config['test_pap_info'] ?? '';
					$opr_key       = $config['test_opr_key'] ?? '';
					$ins_key       = $config['test_ins_key'] ?? '';
					$bundle_js_url = $config['test_bundle_js_url'] ?? '';
					$base_url      = $config['test_base_url'] ?? '';

				}

				$localized['gateway']['getpay_payment'] = array(
					'business_name'  => $business_name,
					'website_domain' => $website_domain,
					'pap_info'       => $pap_info,
					'opr_key'        => $opr_key,
					'ins_key'        => $ins_key,
					'base_url'       => $base_url,
				);
				if ( $payment_page_id ) {
					$localized['gateway']['getpay_payment']['payment_page_url'] = get_permalink( $payment_page_id );
				}
				if ( $processing_page_id ) {
					$localized['gateway']['getpay_payment']['processing_page_url'] = get_permalink( $processing_page_id );
				}

				$localized['gateway']['getpay_payment']['thankyou_page_url'] = Page::get_url( 'thankyou' );
				// $thankyou_page_url = add_query_arg( 'tripzzy_key', $data['tripzzy_nonce'], $thankyou_page_url );
				// $thankyou_page_url = add_query_arg( 'booking_id', $booking_id, $thankyou_page_url );
				// $thankyou_page_url = apply_filters( 'tripzzy_filter_thankyou_page_url', $thankyou_page_url );
			}

			return $localized;
		}
	}
}
