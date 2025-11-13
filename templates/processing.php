<?php
/**
 * Getpay Processing Page.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Settings;

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
