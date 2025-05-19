<?php
/**
 * Post Taxonomies mapped data class
 *
 * @package erikdmitchell\bcmigration\mapping
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\mapping;

/**
 * PostTaxonomiesMappedTerms class
 */
class PostTaxonomiesMappedTerms {

	/**
	 * Mapped term IDs
	 *
	 * @var array
	 */
	private $mapped_term_ids = array();

	/**
	 * Unmapped term IDs
	 *
	 * @var array
	 */
	private $unmapped_term_ids = array();

	/**
	 * Custom mapping for terms
	 *
	 * @var array
	 */
	private $custom_map = array();

	/**
	 * Post ID
	 *
	 * @var int
	 */
	private $post_id = 0;

	/**
	 * Source taxonomy slug
	 *
	 * @var string
	 */
	private $from = '';

	/**
	 * Target taxonomy slug
	 *
	 * @var string
	 */
	private $to = '';

	/**
	 * Constructor for PostTaxonomiesMappedTerms class
	 *
	 * @param string $from       Source taxonomy slug
	 * @param string $to         Target taxonomy slug
	 * @param int    $post_id    Post ID
	 * @param array  $custom_map Custom mapping for terms
	 *
	 * @throws \InvalidArgumentException If taxonomy slug or post ID is invalid
	 */
	public function __construct( string $from = '', string $to = '', int $post_id = 0, array $custom_map = array() ) {
		$this->from       = $from;
		$this->to         = $to;
		$this->post_id    = $post_id;
		$this->custom_map = $custom_map;

		if ( ! $from || ! $to ) {
			throw new \InvalidArgumentException( "Invalid arguments for get_mapped_term_id(): from: $from, to: $to" );
		}

		if ( ! taxonomy_exists( $from ) ) {
			throw new \InvalidArgumentException( "Taxonomy `$from` does not exist." );
		}

		if ( ! taxonomy_exists( $to ) ) {
			throw new \InvalidArgumentException( "Taxonomy `$to` does not exist." );
		}

		if ( ! $post_id ) {
			throw new \InvalidArgumentException( "Invalid post ID: $post_id" );
		}

		$this->setup_term_ids();
	}

	/**
	 * Retrieves the term IDs that have been successfully mapped to the target taxonomy.
	 *
	 * @return array The mapped term IDs.
	 */
	public function get_mapped_term_ids() {
		return $this->mapped_term_ids;
	}

	/**
	 * Retrieves the term IDs of the current post that were not mapped to the
	 * target taxonomy.
	 *
	 * @return array The unmapped term IDs of the current post.
	 */
	public function get_unmapped_term_ids() {
		return $this->unmapped_term_ids;
	}

	/**
	 * Returns the custom mapping of terms between the from and to taxonomies.
	 * This mapping is used to map terms from the from taxonomy to the to taxonomy.
	 * If a term does not exist in the to taxonomy, or if there is no mapping for
	 * the term, it is not mapped.
	 *
	 * @return array The custom mapping of terms.
	 */
	public function get_custom_map() {
		return $this->custom_map;
	}

	/**
	 * Retrieves the term IDs of the current post and checks if they have a mapped
	 * equivalent in the target taxonomy. If not, they are stored in the
	 * unmapped_term_ids property.
	 */
	private function setup_term_ids() {
		$terms = wp_get_object_terms( $this->post_id, $this->from, array( 'fields' => 'ids' ) );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		foreach ( $terms as $term_id ) {
			$mapped_term_id = $this->mapped_term_exists( $term_id, $this->from, $this->to );

			if ( is_wp_error( $mapped_term_id ) ) {
				$this->unmapped_term_ids[] = $term_id;
			} else {
				$this->mapped_term_ids[] = $mapped_term_id;
			}
		}
	}

	/**
	 * Checks if a term exists in the mapped taxonomy.
	 *
	 * @param int    $term_id The term ID in the original taxonomy.
	 * @param string $from    The original taxonomy.
	 * @param string $to      The mapped taxonomy.
	 *
	 * @return int|\WP_Error The term ID in the mapped taxonomy if found, or a WP_Error object if not found.
	 */
	private function mapped_term_exists( int $term_id, string $from, string $to ) {
		$from_term_obj = get_term_by( 'id', $term_id, $from );

		if ( is_wp_error( $from_term_obj ) ) {
			return $from_term_obj;
		}

		$to_term_obj = get_term_by( 'slug', $from_term_obj->slug, $to );

		if ( ! $to_term_obj ) {
			return new \WP_Error( 'term_not_found', "Term '$term_id' not found in taxonomy '$to'." );
		} else {
			return $to_term_obj->term_id;
		}
	}
}
