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
		return wp_update_post(
			array(
				'ID' => $post_id,
				$key => $value,
			)
		);
	}

	public static function update_featured_image( int $post_id = 0, string $field_name = '', array $value = array() ) {
echo "update_featured_image\n";
		if ( empty( $value ) || empty( $value['id'] ) ) {
			return new WP_Error( 'empty_value', 'Featured Image value is empty.' );
		}

		return set_post_thumbnail( $post_id, $value['id'] );
	}

	public static function update_field_value( $args = [] ) {
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
echo "update_field_value\n";
print_r( $args );
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
echo "acf change field type\n";					
					$from_field_value = ACFFieldUtilities::change_field_type( $post_id, $from_acf_field_type, $to_acf_field_type, $from_field_value, $from_field_key );
				}
echo "acf update field value\n";
				$to_field_value = ACFFieldManager::update_field_value( $post_id, $to_field_key, $from_field_value );
				break;

			case 'wp':
echo "wp update field value\n";
				switch ( $to_field_key ) {
					case 'featured_image':	
echo "update featured image\n";										
						if ( ! is_array( $from_field_value ) ) {		
							$from_field_value = array( $from_field_value );
						}

						$result = self::update_featured_image( $post_id, $to_field_key, $from_field_value );
					default:
						$result = PostDataManager::update_post_data( $post_id, $to_field_key, $from_field_value );
				}

				if ( is_wp_error( $result ) ) {
					return $result;
				}

				$to_field_value = $from_field_value;
				break;

			default:
echo "default update field value\n";			
				$to_field_value = update_post_meta( $post_id, $to_field_key, $from_field_value );
		}

		if ( is_wp_error( $to_field_value ) ) {
			return $to_field_value;
		}
// echo "to_field_value\n";		
// print_r( $to_field_value );		
// echo "to_field_value: $to_field_value\n";
		return $to_field_value;
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