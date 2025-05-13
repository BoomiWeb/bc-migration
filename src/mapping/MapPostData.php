<?php
/**
 * Map Post Data class
 *
 * @package erikdmitchell\bcmigration\mapping
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\mapping;

use erikdmitchell\bcmigration\abstracts\MapData;
use erikdmitchell\bcmigration\managers\ACFFieldManager;
use erikdmitchell\bcmigration\managers\PostDataManager;
use erikdmitchell\bcmigration\utilities\ACFFieldUtilities;

/**
 * MapPostData class
 */
class MapPostData extends MapData {

/**
	 * Map post meta from one type to another.
	 *
	 * Logs and adds notices for any errors or skipped posts.
	 *
	 * @param int   $post_id The post ID to migrate.
	 * @param array $meta_map The custom meta mapping.
	 * @param bool  $merge   Whether to merge meta values. Default is false.
	 *
	 * @return void
	 */
	public function map( int $post_id, array $meta_map, bool $merge = false, int $to_post_id = 0 ) {		
		foreach ( $meta_map as $field ) {
			$from_field_type     = $field['from']['type'];
			$from_field_key      = $field['from']['key'];
			$from_acf_field_type = isset( $field['from']['field_type'] ) ? $field['from']['field_type'] : '';
			$from_field_value    = '';
			$to_field_type       = $field['to']['type'];
			$to_field_key        = $field['to']['key'];
			$to_acf_field_type   = isset( $field['to']['field_type'] ) ? $field['to']['field_type'] : '';
			$to_field_value      = '';

			switch ( $from_field_type ) {
				case 'acf':
					$from_field_value = MapACFFields::get_field_value( $post_id, $from_field_key, true );
					break;

				case 'wp':
					$post = get_post( $post_id );

					if ( is_wp_error( $post ) ) {
						$this->log( $post->get_error_message(), 'warning' );
						$this->add_notice( $post->get_error_message(), 'warning' );
						break;
					}

					$from_field_value = $post->{$from_field_key};
					break;

				default:
					$from_field_value = get_post_meta( $post_id, $from_field_key, true );
			}

			if ( is_wp_error( $from_field_value ) ) {
				$this->log( $from_field_value->get_error_message(), 'warning' );
				$this->add_notice( $from_field_value->get_error_message(), 'warning' );

				continue;
			}

			if ( $merge ) {			
				$to_field_value = $this->get_to_field_value( $to_field_type, $to_field_key, $to_post_id );
			}

			if ('' !== $to_field_value) {
				// echo "we have to_field_value\n";
				// echo "to_field_value: $to_field_value\n";
			} else {
				if ( $merge ) {
					$post_id = $to_post_id;
				}

				$this->update_field_value( array(
					'post_id' => $post_id,
					'field_type' => $to_field_type,
					'from_acf_field_type' => $from_acf_field_type,
					'to_acf_field_type' => $to_acf_field_type,
					'from_field_key' => $from_field_key,
					'from_field_value' => $from_field_value,
					'to_field_key' => $to_field_key,
				) );
			}

			// TODO: add param or flag
			// $this->delete_old_meta($post_id, $from_field_key, $from_field_type);

			$this->log( "Copied `$from_field_key` from `$from_field_type` to `$to_field_key` in `$to_field_type`.", 'success' );
			$this->add_notice( "Copied `$from_field_key` from `$from_field_type` to `$to_field_key` in `$to_field_type`.", 'success' );
		}
	}

	/**
	 * Retrieve the value of a field from its type and key.
	 *
	 * @param string $to_field_type The type of the field to retrieve. Can be 'wp' or 'acf'.
	 * @param string $to_field_key   The key of the field to retrieve.
	 * @param int    $to_post_id     The post ID to retrieve the field value from.
	 *
	 * @return mixed The retrieved field value.
	 */
	private function get_to_field_value(string $to_field_type, string $to_field_key, int $to_post_id) {
		switch ( $to_field_type ) {
			case 'acf':
				$to_field_value = get_field( $to_field_key, $to_post_id );
				break;

			case 'wp':
				switch ( $to_field_key ) {
					case 'featured_image':
						$to_field_value = get_post_thumbnail_id( $to_post_id );

						if (0 === $to_field_value) {
							$to_field_value = '';
						}

						break;

					default:
						$to_field_value = get_post_meta( $to_post_id, $to_field_key, true );
				}

				break;
			default:
				$to_field_value = get_post_meta( $to_post_id, $to_field_key, true );
		}

		return $to_field_value;
	}

	/**
	 * Updates a field value based on the provided arguments.
	 *
	 * This method handles updating ACF fields, WordPress post data, or post meta
	 * depending on the type specified. It also handles type conversion for ACF fields
	 * if necessary.
	 *
	 * @param array $args {
	 *     Optional. An array of arguments for the update operation.
	 *
	 *     @type int    $post_id            The post ID to update. Default 0.
	 *     @type string $field_type         The type of the field to update. Can be 'acf' or 'wp'.
	 *     @type string $from_acf_field_type The original ACF field type. Default empty string.
	 *     @type string $to_acf_field_type   The target ACF field type. Default empty string.
	 *     @type string $from_field_key     The key of the field to update. Default empty string.
	 *     @type mixed  $from_field_value   The value to update. Default empty string.
	 *     @type string $to_field_key       The key of the field to update to. Default empty string.
	 * }
	 *
	 * @return mixed The updated field value, or false on failure.
	 */
	private function update_field_value( $args = [] ) {
		$to_field_value = '';
		$defaults = array(
			'post_id' => 0,
			'field_type' => '',
			'from_acf_field_type' => '',
			'to_acf_field_type' => '',
			'from_field_key' => '',
			'from_field_value' => '',
			'to_field_key' => '',
		);
		$args = wp_parse_args( $args, $defaults );

		$post_id         = $args['post_id'];
		$field_type      = $args['field_type'];
		$from_acf_field_type = $args['from_acf_field_type'];
		$to_acf_field_type   = $args['to_acf_field_type'];
		$from_field_value    = $args['from_field_value'];
		$from_field_key      = $args['from_field_key'];
		$to_field_key        = $args['to_field_key'];

		switch ( $field_type ) {
			case 'acf':
				if ( $from_acf_field_type !== $to_acf_field_type ) {
					$from_field_value = ACFFieldUtilities::change_field_type( $post_id, $from_acf_field_type, $to_acf_field_type, $from_field_value, $from_field_key );
				}

				$to_field_value = ACFFieldManager::update_field_value( $post_id, $to_field_key, $from_field_value );
				break;

			case 'wp':
				switch ( $to_field_key ) {
					case 'featured_image':					
						if ( ! is_array( $from_field_value ) ) {		
							$from_field_value = array( $from_field_value );
						}

						$result = PostDataManager::update_featured_image( $post_id, $to_field_key, $from_field_value );
					default:
						$result = PostDataManager::update_post_data( $post_id, $to_field_key, $from_field_value );
				}

				if ( is_wp_error( $result ) ) {
					$this->log( $result->get_error_message(), 'warning' );
					$this->add_notice( $result->get_error_message(), 'warning' );
					break;
				}

				$to_field_value = $from_field_value;
				break;

			default:
				$to_field_value = update_post_meta( $post_id, $to_field_key, $from_field_value );
		}

		if ( is_wp_error( $to_field_value ) ) {
			$this->log( $to_field_value->get_error_message(), 'warning' );
			$this->add_notice( $to_field_value->get_error_message(), 'warning' );

			return false;
		}

		return $to_field_value;
	}	
}
