<?php
/**
 * Map Post Data class
 *
 * @package erikdmitchell\bcmigration\mapping
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\mapping;

use erikdmitchell\bcmigration\managers\PostDataManager;

/**
 * MapPostData class
 */
class MapPostData {


	public function map( int $post_id = 0, array $map = array(), int $to_post_id = 0 ) {
		if (empty( $map ) || empty( $post_id )) {
			return array();
		}

		foreach ( $map as $key => $field ) {
			$from_field_type     = isset( $field['from']['type'] ) ? $field['from']['type'] : '';
			$from_field_key      = isset( $field['from']['key'] ) ? $field['from']['key'] : '';
			$from_field_value    = $this->get_field_value( $from_field_type, $from_field_key, $post_id );

			if (is_wp_error( $from_field_value )) {
				$from_field_value = '';
			}

			$map[$key]['from']['value'] = $from_field_value;

			$to_field_type     = isset( $field['to']['type'] ) ? $field['to']['type'] : '';
			$to_field_key      = isset( $field['to']['key'] ) ? $field['to']['key'] : '';
			$to_field_value    = $this->get_field_value( $to_field_type, $to_field_key, $to_post_id );

			if (is_wp_error( $to_field_value )) {
				$to_field_value = '';
			}

			$map[$key]['to']['value'] = $to_field_value;			
		}

		return $map;
	}

	protected function get_field_value(string $type, string $key, int $post_id) {
		switch ( $type ) {
			case 'acf':
				$value = MapACFFields::get_nested_field_value( $post_id, $key, true );
				// $to_field_value = MapACFFields::get_value( $to_field_key, $to_post_id ); // TODO: used for to_field_value, not sure if needed
				break;

			case 'wp':
				$value = PostDataManager::get_post_data( $post_id, $key );
				break;

			default:
				$value = get_post_meta( $post_id, $key, true );
		}

		return $value;
	}	
}
