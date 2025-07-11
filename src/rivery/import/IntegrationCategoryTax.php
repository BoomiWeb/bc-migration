<?php
/**
 * Rivery import integration category taxonomy class
 *
 * @package erikdmitchell\bcmigration\rivery\import
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\rivery\import;

use erikdmitchell\bcmigration\rivery\Rivery;
use WP_Error;

use function erikdmitchell\bcmigration\get_post_taxonomy_slug_array;

/**
 * IntegrationCategoryTax import class.
 */
class IntegrationCategoryTax {

	/**
	 * The single instance of the class.
	 *
	 * @var IntegrationCategoryTax|null
	 */
	protected static ?IntegrationCategoryTax $instance = null;

	/**
	 * Gets the single instance of the class.
	 *
	 * @return IntegrationCategoryTax Single instance of the class.
	 */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get() {
		$response = Rivery::init()->api->request( 'integration_category' );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'rivery_integration_category_error', 'Failed to fetch Rivery integration categories: ' . $response->get_error_message() );
		}

		if ( empty( $response['body'] ) ) {
			return new WP_Error( 'rivery_integration_category_error', 'No integration category data found in the response.' );
		}

		$categories = json_decode( $response['body'], true );

		if ( ! is_array( $categories ) ) {
			return new WP_Error( 'rivery_integration_category_error', 'Invalid integration category data format.' );
		}

		if ( empty( $categories ) ) {
			return new WP_Error( 'rivery_integration_category_error', 'No integration categories found.' );
		}

		$categories = array_map( array( $this, 'format_category_for_import' ), $categories );

		return $categories;
	}

	private function format_category_for_import( $category ) {
		return array(
			'term_id'     => $category['id'],
			'name'        => $category['name'] ?? '',
			'slug'        => $category['slug'] ?? '',
			'description' => $category['description'] ?? '',
		);
	}
}
