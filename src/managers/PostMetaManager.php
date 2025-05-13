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
	 * Updates post meta for a given post using a mapping file.
	 *
	 * Reads a JSON mapping file to map and update meta fields associated with a
	 * specific post ID. Logs and adds notices if the mapping file is not found or
	 * if a mapping for the specified post type is not found in the file.
	 *
	 * @param int    $post_id The post ID whose meta is to be updated.
	 * @param string $file    Path to the JSON file containing meta mappings.
	 * @param string $from    The source post type to find in the mapping.
	 * @param string $to      The target post type to map meta to.
	 * @param bool   $merge   Whether to merge meta values. Default is false.
	 * @param int    $to_post_id Optional. The post ID to map meta to. Default is 0.
	 *
	 * @return void
	 */
	public static function update(int $post_id, string $file, string $from, string $to, bool $merge = false, int $to_post_id = 0 ) {
		if ( ! file_exists( $file ) ) {
			$this->log( "Mapping file not found: $file", 'warning' );
			$this->add_notice( "Mapping file not found: $file", 'warning' );

			return;
		}

		// check the map for the post type.
		$meta_map          = array();
		$post_type_to_find = $from;
		$mappings          = json_decode( file_get_contents( $file ), true );

		foreach ( $mappings as $mapping ) {
			if ( isset( $mapping['post_type'] ) && $mapping['post_type'] === $post_type_to_find ) {
				$meta_map = $mapping['meta_map'];
				break; // Stop at the first match.
			}
		}

		if ( empty( $meta_map ) ) {
			$this->log( "Mapping not found for post type: $post_type_to_find", 'warning' );
			$this->add_notice( "Mapping not found for post type: $post_type_to_find", 'warning' );

			return;
		}

		$mapper = new MapPostMeta( $this );
		$mapper->map( $post_id, $meta_map, $merge, $to_post_id );	
	}
}