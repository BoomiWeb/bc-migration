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

	/**
	 * Change the type of a field value.
	 *
	 * This method handles the complex process of converting a field value from one type to another.
	 * It is intended to be used when migrating a field from one type to another, and is used
	 * internally by the `bcmigration_update_field_type` command.
	 *
	 * @param int    $post_id     The post ID to update.
	 * @param string $old_type    The old type of the field.
	 * @param string $new_type    The new type of the field.
	 * @param mixed  $value       The value to convert.
	 * @param string $from_field_key The key of the field to convert.
	 *
	 * @return mixed The converted value, or a WP_Error on failure.
	 */
	public static function change_field_type( int $post_id = 0, string $old_type = '', string $new_type = '', $value = '', $from_field_key = '' ) {
		$conversion_key = "$old_type:$new_type";

		$conversions = [
			'repeater:url' => [self::class, 'convert_repeater_to_url'],
			'text:number'  => [self::class, 'convert_text_to_number'],
			// Add more conversion mappings here...
		];

		if ( isset( $conversions[ $conversion_key ] ) ) {
			return call_user_func( $conversions[ $conversion_key ], $post_id, $value, $from_field_key );
		}

		// Default or fallback behavior.
		return $value;
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

	/**
	 * Example logic for converting a repeater to a url.
	 *
	 * In this example, the first subfield named 'url' is extracted from the repeater.
	 *
	 * @param int    $post_id     The post ID.
	 * @param mixed  $value       The value to convert.
	 * @param string $from_field_key The key of the field to convert.
	 *
	 * @return mixed The converted value, or the original value if not converted.
	 */
	protected static function convert_repeater_to_url( int $post_id, $value, $from_field_key ) {
		// Example logic: extract a specific subfield from a repeater.
		if ( is_array( $value ) && isset( $value[0]['url'] ) ) {
			return $value[0]['url']; // Just an example.
		}

		// Default or fallback behavior.
		return apply_filters( 'bcm_convert_repeater_to_url', $value, $from_field_key );
	}

        /**
         * Example logic for converting text to a number.
         *
         * In this example, any numeric string is converted to an integer.
         *
         * @param int    $post_id     The post ID.
         * @param mixed  $value       The value to convert.
         * @param string $from_field_key The key of the field to convert.
         *
         * @return int The converted value, or 0 if not converted.
         */
	protected static function convert_text_to_number( int $post_id, $value, $from_field_key ) {
		return is_numeric( $value ) ? (int) $value : 0;
	}	
}
