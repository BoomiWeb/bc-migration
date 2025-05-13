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
					$from_field_value = MapACFFields::get_nested_field_value( $post_id, $from_field_key, true );
					break;

				case 'wp':
					$from_field_value = PostDataManager::get_post_data( $post_id, $from_field_key );
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
// THIS IS NOT MAPPING
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
// END NOT MAPPING
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
				$to_field_value = MapACFFields::get_value( $to_field_key, $to_post_id );
				break;

			case 'wp':
				$to_field_value = PostDataManager::get_post_data( $to_post_id, $to_field_key );

				break;
			default:
				$to_field_value = get_post_meta( $to_post_id, $to_field_key, true );
		}

		return $to_field_value;
	}
}
