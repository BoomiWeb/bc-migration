<?php
/**
 * Map ACF Fields class
 *
 * @package erikdmitchell\bcmigration\mapping
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\mapping;

/**
 * MapACFFields class
 */
class MapACFFields {

	/**
	 * Retrieve a value from a nested ACF field.
	 *
	 * @param int    $post_id    The post ID to retrieve the field value from.
	 * @param string $field_path The path to the field value, specified as a string
	 *                            of field names separated by slashes.
	 *                            Example: 'repeater/field/sub_field'.
	 * @param bool   $first_only  Whether to return only the first matching value.
	 *
	 * @return mixed The retrieved field value, or a WP_Error object if an error
	 *               occurred.
	 */
	public static function get_field_value( int $post_id = 0, string $field_path = '', bool $first_only = false ) {
		$parts = explode( '/', $field_path );

		if ( empty( $parts ) ) {
			return new WP_Error( 'invalid_field_path', 'Field path is empty or invalid.' );
		}

		$field      = array_shift( $parts ); // Top-level field name.
		$field_data = get_field( $field, $post_id );

		if ( empty( $parts ) ) {
			return $field_data;
		}

		if ( ! is_array( $field_data ) ) {
			return new WP_Error( 'expected_array', "Field '$field' is not an array (expected repeater or flexible content)." );
		}

		$is_flexible = isset( $field_data[0]['acf_fc_layout'] );
		$results     = array();

		foreach ( $field_data as $row_index => $row ) {
			if ( $is_flexible ) {
				if ( $row['acf_fc_layout'] !== $parts[0] ) {
					continue;
				}

				$value     = $row;
				$sub_parts = $parts;
				array_shift( $sub_parts ); // Remove layout name.

				foreach ( $sub_parts as $key ) {
					if ( isset( $value[ $key ] ) ) {
						$value = $value[ $key ];
					} else {
						return new WP_Error( 'missing_key', "Key '$key' not found in layout '{$parts[0]}' at index $row_index." );
					}
				}

				if ( $first_only ) {
					return $value;
				}

				$results[] = $value;
			} else {
				$value = $row;

				foreach ( $parts as $key ) {
					if ( isset( $value[ $key ] ) ) {
						$value = $value[ $key ];
					} else {
						return new WP_Error( 'missing_key', "Key '$key' not found in repeater row at index $row_index." );
					}
				}

				if ( $first_only ) {
					return $value;
				}

				$results[] = $value;
			}
		}

		if ( ! empty( $results ) ) {
			return $results;
		}

		$context = $is_flexible ? 'flexible layout' : 'repeater';

		return new WP_Error( 'no_matches', "No matching rows found in $context '$field'." );
	}

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
}
