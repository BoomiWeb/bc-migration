<?php
/**
 * Utilities for ACF fields
 *
 * @package erikdmitchell\bcmigration\utilities
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\utilities;

/**
 * ACFFieldUtilities class
 */
class ACFFieldUtilities {

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

		$conversions = array(
			'repeater:url' => array( self::class, 'convert_repeater_to_url' ),
			'text:number'  => array( self::class, 'convert_text_to_number' ),
			// Add more conversion mappings here...
		);

		if ( isset( $conversions[ $conversion_key ] ) ) {
			return call_user_func( $conversions[ $conversion_key ], $post_id, $value, $from_field_key );
		}

		// Default or fallback behavior.
		return $value;
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