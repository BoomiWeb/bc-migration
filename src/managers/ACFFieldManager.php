<?php
/**
 * Manager for ACF fields
 *
 * @package erikdmitchell\bcmigration\managers
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\managers;

/**
 * ACFFieldManager class
 */
class ACFFieldManager {

    /**
	 * Updates an ACF field value.
	 *
	 * If the field type is a link, it will be updated as an array with a single key 'url'.
	 *
	 * @param int    $post_id   The post ID.
	 * @param string $field_name The field name.
	 * @param mixed  $value      The value to update.
	 *
	 * @return mixed The updated field value, or a WP_Error on failure.
	 */
	public static function update_field_value( int $post_id = 0, string $field_name = '', $value = '' ) {
		$field_object = get_field_object( $field_name, $post_id );

		if ( isset( $field_object['type'] ) && 'link' === $field_object['type'] ) {
			$value = array(
				'url' => $value,
			);
		}

		$updated = update_field( $field_name, $value, $post_id );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		// this will return an array in some cases like link.
		return get_field( $field_name, $post_id );
	}

    /**
	 * Delete an ACF field value.
	 *
	 * @param int    $post_id     The post ID to delete the field value from.
	 * @param string $field_name  The name of the field to delete.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function delete_field( int $post_id = 0, string $field_name = '' ) {
		return delete_field( $field_name, $post_id );
	}
}