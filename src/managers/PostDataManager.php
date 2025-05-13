<?php
/**
 * Manager for post data
 *
 * @package erikdmitchell\bcmigration\managers
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\managers;

/**
 * PostDataManager class
 */
class PostDataManager {

	/**
	 * Updates a post meta value.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $key     The meta key.
	 * @param mixed  $value   The new value.
	 *
	 * @return int|WP_Error The post ID if successful, or a WP_Error on failure.
	 */
	public static function update_post_data( int $post_id = 0, string $key = '', $value = '' ) {
		return wp_update_post(
			array(
				'ID' => $post_id,
				$key => $value,
			)
		);
	}

	/**
	 * Updates the featured image of a post.
	 *
	 * @param int    $post_id   The post ID.
	 * @param string $field_name The field name.
	 * @param array  $value      The value to update. Must include an 'id' key.
	 *
	 * @return int|WP_Error The ID of the attachment or a WP_Error on failure.
	 */
	public static function update_featured_image( int $post_id = 0, string $field_name = '', array $value = array() ) {
		if ( empty( $value ) || empty( $value['id'] ) ) {
			return new WP_Error( 'empty_value', 'Featured Image value is empty.' );
		}

		return set_post_thumbnail( $post_id, $value['id'] );
	}
}    