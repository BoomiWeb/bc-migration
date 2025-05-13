<?php
/**
 * Manager for post meta
 *
 * @package erikdmitchell\bcmigration\managers
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\managers;

/**
 * PostMetaManager class
 */
class PostMetaManager {

    /**
	 * Delete the old field value from the old field type.
	 *
	 * @param int    $post_id     The post ID to delete the field value from.
	 * @param string $field_key   The key of the field to delete.
	 * @param string $field_type  The type of the field to delete. Can be 'wp' or 'acf'.
	 *
	 * @return void
	 */
	public function delete_old_meta( int $post_id, string $field_key, string $field_type ) {
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