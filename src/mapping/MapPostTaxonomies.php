<?php
/**
 * Map Post Taxonomies class
 *
 * @package erikdmitchell\bcmigration\mapping
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\mapping;

use erikdmitchell\bcmigration\abstracts\MapPostData;

/**
 * MapPostTaxonomies Class
 */
class MapPostTaxonomies extends MapPostData {

	/**
	 * Map post taxonomies from one type to another.
	 *
	 * @param int   $post_id The post ID to migrate.
	 * @param array $tax_map The custom taxonomy mapping.
	 */
	public function map( int $post_id, array $tax_map ) {
		$tax_terms = array();

		foreach ( $tax_map as $obj ) {
			$custom_map = isset( $obj->map ) ? $obj->map : array();

			try {
				$tax_mapper = new PostTaxonomiesMappedTerms( $obj->from, $obj->to, $post_id, $custom_map );

				$mapped_term_ids   = $tax_mapper->get_mapped_term_ids();
				$unmapped_term_ids = $tax_mapper->get_unmapped_term_ids();

				if ( ! empty( $unmapped_term_ids ) ) {
					foreach ( $unmapped_term_ids as $term_id ) {
						$term = get_term( $term_id );

						if ( is_wp_error( $term ) ) {
							$this->log( $term->get_error_message(), 'warning' );
							$this->add_notice( $term->get_error_message(), 'warning' );
							continue;
						}

						if ( ! empty( $custom_map ) ) {
							$match = $this->find_match( $custom_map, $term->slug );
							if ( ! $match ) {
								continue;
							}
							$term_name = $match;
						} else {
							$term_name = $term->name;
						}

						$new_term_data = wp_insert_term( $term_name, $obj->to, array( 'slug' => $term->slug ) );

						if ( is_wp_error( $new_term_data ) ) {
							$this->log( $new_term_data->get_error_message(), 'warning' );
							$this->add_notice( $new_term_data->get_error_message(), 'warning' );
							continue;
						}

						$tax_terms[] = $new_term_data['term_id'];
					}
				}

				if ( ! empty( $mapped_term_ids ) ) {
					foreach ( $mapped_term_ids as $term_id ) {
						$tax_terms[] = $term_id;
					}
				}

				$set_term_ids = wp_set_object_terms( $post_id, $tax_terms, $obj->to );

				if ( is_wp_error( $set_term_ids ) ) {
					$this->log( $set_term_ids->get_error_message(), 'warning' );
					$this->add_notice( $set_term_ids->get_error_message(), 'warning' );
				}

				$this->log( "Copied terms from `$obj->from` to `$obj->to`.", 'success' );
				$this->add_notice( "Copied terms from `$obj->from` to `$obj->to`.", 'success' );

			} catch ( \InvalidArgumentException $e ) {
				$this->log( $e->getMessage(), 'warning' );
				$this->add_notice( $e->getMessage(), 'warning' );
				continue;
			}
		}
	}

	/**
	 * Searches a mapping array for a matching from value.
	 *
	 * @param array  $map The mapping array.
	 * @param string $search The value to search for.
	 *
	 * @return string|null The matching to value or null if no match is found.
	 */
	private function find_match( array $map, string $search ) {
		foreach ( $map as $item ) {
			if ( $item->from === $search ) {
				return $item->to;
			}
		}

		return null; // no match found.
	}
}
