<?php
/**
 * Manager for post data
 *
 * @package erikdmitchell\bcmigration\managers
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\managers;

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

	/**
	 * Updates a post meta value.
	 *
	 * @param int    $post_id The post ID.
	 * @param string $key     The meta key.
	 * @param mixed  $value   The new value.
	 *
	 * @return int|WP_Error The post ID if successful, or a WP_Error on failure.
	 */
	public static function update_post_data( int $post_id = 0, string $key = '', $value = '' ) {
		return wp_update_post(
			array(
				'ID' => $post_id,
				$key => $value,
			)
		);
	}

	/**
	 * Updates the featured image of a post.
	 *
	 * @param int    $post_id   The post ID.
	 * @param string $field_name The field name.
	 * @param array  $value      The value to update. Must include an 'id' key.
	 *
	 * @return int|WP_Error The ID of the attachment or a WP_Error on failure.
	 */
	public static function update_featured_image( int $post_id = 0, string $field_name = '', array $value = array() ) {
		if ( empty( $value ) || empty( $value['id'] ) ) {
			return new WP_Error( 'empty_value', 'Featured Image value is empty.' );
		}

		return set_post_thumbnail( $post_id, $value['id'] );
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
	public function update_field_value( $args = [] ) {
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