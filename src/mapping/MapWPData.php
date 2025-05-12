<?php
/**
 * Map WP Data class
 *
 * @package erikdmitchell\bcmigration\mapping
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\mapping;

use WP_Error;

/**
 * MapWPData class
 */
class MapWPData {

	public static function update_post_data( int $post_id = 0, string $key = '', $value = '' ) {
		return wp_update_post(
			array(
				'ID'          => $post_id,
				$key => $value,
			)
		);
	}	

	public static function update_featured_image( int $post_id = 0, string $field_name = '', array $value = array() ) {
		if ( empty( $value ) ) {
			return new WP_Error( 'empty_value', 'Featured Image value is empty.' );
		}

		return set_post_thumbnail( $post_id, $value['id'] );
	}
}
