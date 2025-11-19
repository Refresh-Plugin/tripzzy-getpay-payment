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
use Tripzzy\Core\Template;

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

			// Add Query vers for processing/success page.
			add_filter( 'query_vars', array( $this, 'query_vars' ) );

			add_shortcode( 'TRIPZZY_GETPAY_SUCCESS_PAGE', array( $this, 'getpay_success_page_shortcode' ) );
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
			$settings = Settings::get();
			$data     = self::geteway_data();

			if ( ! empty( $data ) ) {
				$test_mode     = $data['test_mode'];
				$config        = $data['config']; // Payment gateway configuration.
				$bundle_js_url = $config['bundle_js_url'] ?? '';
				if ( $test_mode ) {
					$bundle_js_url = $config['test_bundle_js_url'] ?? '';
				}

				wp_register_script( 'tripzzy-getpay-bundle', $bundle_js_url, array(), '1.0.0', true );
				wp_register_script( 'tripzzy-getpay-local-storage', self::$assets_url . 'local-storage.js', array(), '1.0.0', true );
				wp_register_script( 'tripzzy-getpay-custom', self::$assets_url . 'getpay.js', array( 'tripzzy-getpay-bundle', 'tripzzy-getpay-local-storage' ), '1.0.0', true );
				wp_register_style( 'tripzzy-getpay-custom', self::$assets_url . 'getpay.css', array(), '1.0.0' );

				if ( $this->is_page( 'payment' ) || $this->is_page( 'success' ) ) {

					if ( $this->is_page( 'payment' ) ) {
						wp_enqueue_script( 'tripzzy-getpay-bundle' );
						wp_enqueue_script( 'tripzzy-getpay-preloader', self::$assets_url . 'getpay-preloader.js', array(), '1.0.0', true );
					}

					wp_enqueue_style( 'tripzzy-getpay-preloader', self::$assets_url . 'getpay-preloader.css', array(), '1.0.0' );
					wp_enqueue_style( 'tripzzy-getpay-custom' );

					add_action( 'wp_footer', array( $this, 'add_loader' ) );

				}
			}
		}

		/**
		 * Add div in Tripzzy Checkout page with id checkout.
		 *
		 * @return void
		 */
		public function add_checkout_div() {
			wp_enqueue_script( 'tripzzy-getpay-custom' );
			wp_enqueue_style( 'tripzzy-getpay-custom' );
			?>
			<div id="checkout" hidden></div>
			<?php
		}

		/**
		 * Add Loader for payment and success page.
		 *
		 * @return void
		 */
		public function add_loader() {
			$loading_text = 'Loading secure payment...';
			if ( $this->is_page( 'success' ) ) {
				$loading_text = 'Processing your bookings...';
			}

			?>
				<div id="getpay-preloader">
					<img src="<?php echo esc_url( self::$assets_url . 'logo.webp' ); ?>" alt="GetPay" class="getpay-logo">
					<div id="loading-text"><?php echo esc_html( $loading_text ); ?></div>
				</div>
			<?php
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
					$page_id = (int) $settings['getpay_payment_page_id'] ?? 0;
					return $page_id && $page_id === $current_page_id;
				case 'success':
					$page_id = (int) $settings['getpay_success_page_id'] ?? 0;
					return $page_id && $page_id === $current_page_id;
				case 'failed':
					$page_id = (int) $settings['getpay_failed_page_id'] ?? 0;
					return $page_id && $page_id === $current_page_id;
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
				$settings        = self::$settings;
				$test_mode       = $data['test_mode'];
				$config          = $data['config']; // Payment gateway configuration.
				$payment_page_id = $settings['getpay_payment_page_id'] ?? 0;
				$success_page_id = $settings['getpay_success_page_id'] ?? 0;
				$failed_page_id  = $settings['getpay_failed_page_id'] ?? 0;

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
				if ( $success_page_id ) {
					$localized['gateway']['getpay_payment']['success_page_url'] = get_permalink( $success_page_id );
				}
				if ( $failed_page_id ) {
					$localized['gateway']['getpay_payment']['failed_page_url'] = get_permalink( $failed_page_id );
				}
				ob_start();

				Template::get_template_part( 'layouts/default/partials/mini', 'cart' );
				$order_information_ui = ob_get_contents();
				ob_end_clean();
				$localized['gateway']['getpay_payment']['order_information_ui'] = $order_information_ui;

			}

			return $localized;
		}

		public function getpay_success_page_shortcode() {
			wp_enqueue_script( 'tripzzy-getpay-redirect-success', self::$assets_url . 'getpay-redirect-success.js', array(), '1.0.0', true );

			$token = sanitize_text_field( wp_unslash( get_query_var( 'token' ) ) );

			if ( ! $token ) {
				return;
			}

			$settings    = Settings::get();
			$test_mode   = (bool) ( $settings['test_mode'] ?? false );
			$getpay_data = $settings['payment_gateways']['getpay_payment'] ?? array();

			if ( ! $getpay_data || ! is_array( $getpay_data ) ) {
				return;
			}

			$pap_info = isset( $getpay_data['pap_info'] ) ? $getpay_data['pap_info'] : '';
			$base_url = isset( $getpay_data['base_url'] ) ? $getpay_data['base_url'] : '';
			if ( $test_mode ) {
				$pap_info = isset( $getpay_data['test_pap_info'] ) ? $getpay_data['test_pap_info'] : '';
				$base_url = isset( $getpay_data['test_base_url'] ) ? $getpay_data['test_base_url'] : '';
			}

			if ( empty( $pap_info ) || empty( $base_url ) ) {

				return;

			}

			$transaction_id = tripzzy_get_transaction_id_by_token( $token );

			if ( ! $transaction_id ) {
				return;
			}

			$response = tripzzy_check_transaction_status( $transaction_id, $pap_info, $base_url );

			return '';
		}
	}
}
