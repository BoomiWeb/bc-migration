<?php
/**
 * Setups the base taxonomies functionality
 *
 * @package     erikdmitchell\bcmigration\Abstracts
 * @since     0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\abstracts;

use WP_Error;

/**
 * Taxonomies Class
 */
abstract class Taxonomies {

	/**
	 * Retrieve terms from a taxonomy.
	 *
	 * @param string $term Optional. Term name or slug to search for.
	 * @return array|WP_Error The terms data or WP_Error on failure.
	 */
	public function get_terms( string $term = '' ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $term,
				'hide_empty' => false,
			)
		);

		return $terms;
	}

	/**
	 * Retrieves the taxonomies associated with the given post type.
	 *
	 * @param string $post_type Optional. Post type to retrieve taxonomies for.
	 * @return string[] Array of taxonomy names.
	 */
	public function get_by_post_type( string $post_type = '' ) {
		$taxonomies = get_object_taxonomies( $post_type, 'names' );

		return $taxonomies;
	}

	/**
	 * Rename a taxonomy term by slug or name.
	 *
	 * @param string      $taxonomy The taxonomy slug (e.g., 'category', 'industries').
	 * @param string      $old_term The current term name or slug to search for.
	 * @param string      $new_name The new name you want to assign.
	 * @param string|null $new_slug (Optional) New slug for the term.
	 * @return array{term_id: int, term_taxonomy_id: int}|WP_Error The updated term data or WP_Error on failure.
	 */
	public function rename( string $taxonomy, string $old_term, string $new_name, $new_slug = null ) {
		// Try to get the term by slug first, then name if that fails.
		$term = get_term_by( 'slug', sanitize_title( $old_term ), $taxonomy );

		if ( ! $term ) {
			$term = get_term_by( 'name', $old_term, $taxonomy );
		}

		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'term_not_found', "Term '$old_term' not found in taxonomy '$taxonomy'." );
		}

		$args = array( 'name' => $new_name );
		if ( $new_slug ) {
			$args['slug'] = sanitize_title( $new_slug );
		}

		return wp_update_term( $term->term_id, $taxonomy, $args );
	}
}
