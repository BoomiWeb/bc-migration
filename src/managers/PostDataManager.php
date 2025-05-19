<?php
/**
 * Manager for post data
 *
 * @package erikdmitchell\bcmigration\managers
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\managers;

use erikdmitchell\bcmigration\utilities\ACFFieldUtilities;
use WP_Error;

/**
 * PostDataManager class
 */
class PostDataManager {

    public static function get_post_data(int $post_id = 0, string $key = '') {
        $post = get_post( $post_id );

        if ( is_wp_error( $post ) ) {
            $this->log( $post->get_error_message(), 'warning' );
            $this->add_notice( $post->get_error_message(), 'warning' );
            
            return '';
        }

        switch ( $key ) {
            case 'featured_image':
                $value = get_post_thumbnail_id( $post_id );

                if (0 === $value) {
                    $value = '';
                }

                break;

            default:
                $value = isset( $post->{$key} ) ? $post->{$key} : '';
        }        

        return $value;
    }

	public static function update_post_data( int $post_id = 0, string $key = '', $value = '' ) {
echo "update_post_data\n";		
		return wp_update_post(
			array(
				'ID' => $post_id,
				$key => $value,
			)
		);
	}

	public static function update_featured_image( int $post_id = 0, string $field_name = '', array $value = array() ) {
		if ( empty( $value ) || empty( $value['id'] ) ) {
			return new WP_Error( 'empty_value', 'Featured Image value is empty.' );
		}

		return set_post_thumbnail( $post_id, $value['id'] );
	}

	public static function update_field_value( $args = [] ) {
// echo "update_field_value\n";		
		$updated_value = '';
		$defaults = array(
			'post_id' => 0,
			'type' => '',
			'key' => '',
			'value' => '',
		);
		$args = wp_parse_args( $args, $defaults );
// print_r($args);
		$post_id         = $args['post_id'];
		$type = $args['type'];
		$key = $args['key'];
		$value = $args['value'];

		switch ( $type ) {
			case 'acf':
				$updated_value = ACFFieldManager::update_field_value( $post_id, $key, $value );
				break;

			case 'wp':
// echo "update field value (wp): $key\n";				
				switch ( $key ) {
					case 'featured_image':										
						if ( ! is_array( $value ) ) {		
							$value = array( $value );
						}

						$result = self::update_featured_image( $post_id, $key, $value );
					default:
						$result = PostDataManager::update_post_data( $post_id, $key, $value );
				}

				if ( is_wp_error( $result ) ) {
					return $result;
				}

				$updated_value = $value;
				break;

			default:		
				$updated_value = update_post_meta( $post_id, $key, $value );
		}

		if ( is_wp_error( $updated_value ) ) {
			return $updated_value;
		}

		return $updated_value;
	}

	public static function delete_field_value( int $post_id, string $field_key, string $field_type ) {
		switch ( $field_type ) {
			case 'acf':
				$result = ACFFieldManager::delete_field( $post_id, $field_key );
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