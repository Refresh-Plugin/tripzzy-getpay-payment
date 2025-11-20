<?php
/**
 * Getpay Functions.
 *
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Decode Token send by Getpay to getpay success url.
 *
 * @param string $token Base 64 base Token.
 * @return string
 */
function tripzzy_getpay_get_transaction_id_by_token( $token ) {

	// Decode the Base64 token.
	$decoded_json = base64_decode( $token );

	// Convert JSON string into an associative .
	$data = json_decode( $decoded_json, true );
	// Check if the decoded data contains 'tid' (transaction ID).
	if ( isset( $data['id'] ) ) {
		return $data['id'];
	}

	return null; // Return null if transaction ID is missing.
}

/**
 * Getpay Check Transaction Status
 *
 * @param string $transaction_id Getpay Transaction ID.
 * @param string $pap_info Key provided by getpay.
 * @param string $base_url Base URL to check the status. Provided by Getpay.
 * @return array
 */
function tripzzy_getpay_check_transaction_status( $transaction_id, $pap_info, $base_url ) {

	$api_url = $base_url . '/merchant-status';

	$body = array(
		'id'      => $transaction_id,
		'papInfo' => $pap_info,
	);

	$response = wp_remote_post(
		$api_url,
		array(
			'method'  => 'POST',
			'body'    => json_encode( $body ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 30,
		)
	);

	if ( is_wp_error( $response ) ) {

		return array(
			'status'  => 'error',
			'message' => $response->get_error_message(),
		);

	}

	$response_body = wp_remote_retrieve_body( $response );

	$result = json_decode( $response_body, true );

	return $result;
}

/**
 * Check Booking exist by Transaction ID.
 *
 * @param string $transaction_id Getpay Transaction ID.
 * @return bool
 */
function tripzzy_getpay_check_booking_exists_by_transaction_id( $transaction_id = '' ) {
	if ( ! $transaction_id ) {
		false;
	}

	$args = array(
		'post_type'      => 'tripzzy_booking',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'   => 'tripzzy_getpay_transaction_id',
				'value' => $transaction_id,
			),
		),
	);

	$query = new WP_Query( $args );
	return ! empty( $query->posts );
}
