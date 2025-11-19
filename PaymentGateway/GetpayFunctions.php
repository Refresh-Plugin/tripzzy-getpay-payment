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
 * @return void
 */
function tripzzy_get_transaction_id_by_token( $token ) {

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

function tripzzy_check_transaction_status( $transaction_id, $papInfo, $base_url ) {

	$api_url = $base_url . '/merchant-status';

	$body = array(
		'id'      => $transaction_id,
		'papInfo' => $papInfo,
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
