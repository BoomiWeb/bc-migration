<?php
/**
 * Manager for taxonomies
 *
 * @package erikdmitchell\bcmigration\managers
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\managers;

/**
 * TaxonomyManager class
 */
class TaxonomyManager {

	/**
	 * Copies terms from one post type to another post type.
	 *
	 * Ensures that the taxonomies associated with the `from` post type are also
	 * associated with the `to` post type. Then, copies the terms from the `from`
	 * post type to the `to` post type.
	 *
	 * Logs and adds notices for any errors or skipped posts.
	 *
	 * @param int    $post_id The post ID to migrate.
	 * @param string $from    The source post type to copy terms from.
	 * @param string $to      The target post type to copy terms to.
	 *
	 * @return void
	 */
	public static function copy_terms( int $post_id, string $from, string $to ) {
		$attached = $this->ensure_taxonomies_attached( $from, $to );

		if ( ! $attached ) {
			$this->log( 'Taxonomies not attached.', 'warning' );
			$this->add_notice( 'Taxonomies not attached.', 'warning' );

			return;
		}

		$taxonomies = get_object_taxonomies( $from );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );

			if ( ! is_wp_error( $terms ) ) {
				$terms_set = wp_set_object_terms( $post_id, $terms, $taxonomy );

				if ( is_wp_error( $terms_set ) ) {
					$this->log( "Failed to copy terms from `$from`.", 'warning' );
					$this->add_notice( "Failed to copy terms from `$from`.", 'warning' );

					continue;
				}

				$this->log( "Copied terms from `$from`." );
				$this->add_notice( "Copied terms from `$from`." );
			}
		}
	}  
    
	/**
	 * Updates taxonomies for a given post using a mapping file.
	 *
	 * Reads a JSON mapping file to map and update taxonomies associated with a
	 * specific post ID. Logs and adds notices if the mapping file is not found.
	 *
	 * @param int    $post_id The post ID whose taxonomies are to be updated.
	 * @param string $file    Path to the JSON file containing taxonomy mappings.
	 * @param bool   $merge   Optional. Whether to merge taxonomies. Default is false.
	 *
	 * @return void
	 */
	public static function update( int $post_id, string $file, bool $merge = false, int $to_post_id = 0 ) {			
		if ( ! file_exists( $file ) ) {
			$this->log( "Mapping file not found: $file", 'warning' );
			$this->add_notice( "Mapping file not found: $file", 'warning' );

			return;
		}

		$tax_map = json_decode( file_get_contents( $file ) );

// TODO setup and test merge		

		$mapper = new MapPostTaxonomies( $this );
		$mapper->map( $post_id, $tax_map );		
	}    

	/**
	 * Ensures that taxonomies from one post type are attached to another post type.
	 *
	 * Iterates through the taxonomies associated with the `from` post type and attaches
	 * them to the `to` post type if they are not already associated.
	 *
	 * @param string $from The source post type to retrieve taxonomies from.
	 * @param string $to   The target post type to attach taxonomies to.
	 *
	 * @return bool Returns false if no taxonomies are found for the `from` post type.
	 */
	private static function ensure_taxonomies_attached( $from, $to ) {
		$from_taxonomies = get_object_taxonomies( $from, 'objects' );

		if ( empty( $from_taxonomies ) ) {
			return false;
		}

		foreach ( $from_taxonomies as $taxonomy => $taxonomy_obj ) {
			if ( ! in_array( $to, $taxonomy_obj->object_type, true ) ) {
				$taxonomy_obj->object_type[] = $to;

				$registered_taxonomy_object = register_taxonomy( $taxonomy, $taxonomy_obj->object_type, (array) $taxonomy_obj );

				if ( is_wp_error( $registered_taxonomy_object ) ) {
					$this->log( "Failed to attach `$taxonomy` to `$to`.", 'warning' );
					$this->add_notice( "Failed to attach `$taxonomy` to `$to`.", 'warning' );

					continue;
				}

				$this->log( "Attached taxonomy `$taxonomy` to `$to`.", 'success' );
				$this->add_notice( "Attached taxonomy `$taxonomy` to `$to`.", 'success' );
			}
		}
	}
}