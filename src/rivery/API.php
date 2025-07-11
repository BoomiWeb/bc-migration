<?php
/**
 * Rivery API class
 *
 * @package erikdmitchell\bcmigration\rivery
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\rivery;

use WP_Error;

class API {

	/**
	 * The Rivery API settings.
	 *
	 * @var AdminSettings
	 */
	private AdminSettings $settings;

	/**
	 * The single instance of the class.
	 *
	 * @var API|null
	 */
	protected static ?API $instance = null;

	/**
	 * Initializes the class and sets the database properties.
	 *
	 * @internal
	 */
	private function __construct() {
		$this->setup_settings();
	}

	private function setup_settings() {
		$this->settings = new AdminSettings();
	}

	/**
	 * Checks if API credentials are set.
	 *
	 * @return bool
	 */
	public function has_credentials(): bool {
		return ! empty( $this->settings->get_credentials() );
	}

	/**
	 * Gets the single instance of the class.
	 *
	 * @return API Single instance of the class.
	 * @throws \RuntimeException If credentials are missing.
	 */
	public static function init() {
		if ( ! self::$instance ) {
			$instance = new self();

			if ( ! $instance->has_credentials() ) {
				throw new \RuntimeException( 'Rivery API credentials are missing.' );
			}

			self::$instance = $instance;
		}

		return self::$instance;
	}

	/**
	 * Makes a request to the Rivery API.
	 *
	 * @param string $endpoint The API endpoint to request.
	 * @param string $method   The HTTP method to use (GET, POST, etc.).
	 * @param array  $data     The data to send with the request (for POST/PUT).
	 * @return array|WP_Error The response from the API or an error object.
	 */
	public function request( $endpoint, $method = 'GET', $data = array() ) {
		if ( ! $this->settings->has_credentials() ) {
			return new WP_Error( 'no_credentials', 'API credentials not configured' );
		}
		// TODO: cache with wp transient
		$creds = $this->settings->get_credentials();
		$url   = trailingslashit( $creds['api_url'] ) . ltrim( $endpoint, '/' );

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $creds['username'] . ':' . $creds['password'] ),
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		);

		if ( ! empty( $data ) ) {
			if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ) ) ) {
				$args['body'] = wp_json_encode( $data );
			} elseif ( $method === 'GET' ) {
				$url = add_query_arg( $data, $url );
			}
		}

		return wp_remote_request( $url, $args );
	}
}
