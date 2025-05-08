<?php
/**
 * Map Post Meta class
 *
 * @package erikdmitchell\bcmigration\mapping
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\mapping;

class MapPostMeta {

    public static function init() {}

    private function meta_map( int $post_id, array $meta_map ) {
		// TODO: do we remove old meta.
		// $post_type = $meta_map['post_type']; NOT USED.
		$meta_fields_map = $meta_map['meta_map'];

		foreach ( $meta_fields_map as $field ) {
			$from_field_type = $field['from']['type'];		
			$from_field_key = $field['from']['key'];			
			$from_field_value = '';
			$to_field_type = $field['to']['type'];		
			$to_field_key = $field['to']['key'];			
			$to_field_value = '';			
		
			switch ( $from_field_type ) {
				case 'acf':
					// This either returns the value or a wp error
					$from_field_value = MapACFFields::get_field_value( $post_id, $from_field_key, true );

					break;
				case 'wp':
					$post = get_post( $post_id );

					if ( is_wp_error( $post ) ) {
						$this->log( $post->get_error_message(), 'warning' );
						$this->add_notice( $post->get_error_message(), 'warning' );

						continue;
					}

					// check it exists
					$from_field_value = $post->{$from_field_key};
					break;
				default:
					$from_field_value = get_post_meta( $post_id, $from_field_key, true );
			}

			// make sure we have a value to set and it's not an error.
			if ( empty($from_field_value) || is_wp_error( $from_field_value ) ) {
				continue;
			}

			switch ( $to_field_type ) {
				case 'acf':
					// This either returns the value or a wp error - the value can be an array
					$to_field_value = MapACFFields::update_field_value( $post_id, $to_field_key, $from_field_value );
					break;
				case 'wp':
					$post_id = wp_update_post( array( 'ID' => $post_id, $to_field_key => $from_field_value ) );

					if ( is_wp_error( $post_id ) ) {
						$this->log( $post_id->get_error_message(), 'warning' );
						$this->add_notice( $post_id->get_error_message(), 'warning' );

						continue;
					}

					$to_field_value = $from_field_value;
					break;
				default:
					$to_field_value = update_post_meta( $post_id, $to_field_key, $from_field_value );
			}

			if ( is_wp_error( $to_field_value ) ) {
				$this->log( $to_field_value->get_error_message(), 'warning' );
				$this->add_notice( $to_field_value->get_error_message(), 'warning' );
			}

			$this->log( "Copied `$from_field_key` from `$from_field_type` to `$to_field_key` in `$to_field_type`.", 'success' );
			$this->add_notice( "Copied `$from_field_key` from `$from_field_type` to `$to_field_key` in `$to_field_type`.", 'success' );
		};
	}    

}