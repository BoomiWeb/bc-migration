<?php
/**
 * Map Post Meta class
 *
 * @package erikdmitchell\bcmigration\mapping
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\mapping;

use erikdmitchell\bcmigration\abstracts\MapPostData;

/**
 * MapPostMeta class
 */
class MapPostMeta extends MapPostData {

	/**
	 * Map post meta from one type to another.
	 *
	 * Logs and adds notices for any errors or skipped posts.
	 *
	 * @param int   $post_id The post ID to migrate.
	 * @param array $meta_map The custom meta mapping.
	 *
	 * @return void
	 */
	public function map( int $post_id, array $meta_map ) {
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

			switch ( $to_field_type ) {
				case 'acf':
					if ( $from_acf_field_type !== $to_acf_field_type ) {
						$from_field_value = MapACFFields::change_field_type( $post_id, $from_acf_field_type, $to_acf_field_type, $from_field_value, $from_field_key );
					}

					$to_field_value = MapACFFields::update_field_value( $post_id, $to_field_key, $from_field_value );
					break;

				case 'wp':
					switch ( $to_field_key ) {
						case 'featured_image':
							if ( ! is_array( $from_field_value ) ) {
								$from_field_value = array( $from_field_value );
							}

							$result = MapWPData::update_featured_image( $post_id, $to_field_key, $from_field_value );
						default:
							$result = MapWPData::update_post_data( $post_id, $to_field_key, $from_field_value );
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

				continue;
			}

			// TODO: add param or flag
			// $this->delete_old_meta($post_id, $from_field_key, $from_field_type);

			$this->log( "Copied `$from_field_key` from `$from_field_type` to `$to_field_key` in `$to_field_type`.", 'success' );
			$this->add_notice( "Copied `$from_field_key` from `$from_field_type` to `$to_field_key` in `$to_field_type`.", 'success' );
		}
	}

	/**
	 * Delete the old field value from the old field type.
	 *
	 * @param int    $post_id     The post ID to delete the field value from.
	 * @param string $field_key   The key of the field to delete.
	 * @param string $field_type  The type of the field to delete. Can be 'wp' or 'acf'.
	 *
	 * @return void
	 */
	protected function delete_old_meta( int $post_id, string $field_key, string $field_type ) {
		switch ( $field_type ) {
			case 'acf':
				$result = MapACFFields::delete_field( $post_id, $field_key );
				break;

			default:
				$result = delete_post_meta( $post_id, $field_key );
		}

		if ( ! $result ) {
			$this->log( "Failed to delete `$field_key` from `$field_type`.", 'warning' );
			$this->add_notice( "Failed to delete `$field_key` from `$field_type`.", 'warning' );
		}

		$this->log( "Deleted `$field_key` from `$field_type`.", 'success' );
		$this->add_notice( "Deleted `$field_key` from `$field_type`.", 'success' );
	}
}
