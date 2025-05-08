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

class MapPostTaxonomies extends MapPostData {

    // public static function init() {}

    public static function map( int $post_id, array $tax_map ) {					
		$tax_terms = array();

		foreach ( $tax_map as $obj ) {
            $tax_mapper = new PostTaxonomies( $obj->from, $obj->to, $post_id );
			$mapped_term_ids = $tax_mapper->get_mapped_term_ids();
			$unmapped_term_ids = $tax_mapper->get_unmapped_term_ids();

			if ( !empty($unmapped_term_ids) ) {
				foreach ( $unmapped_term_ids as $term_id ) {
					$term = get_term($term_id);

					$new_term_data = wp_insert_term( $term->name, $obj->to, array( 'slug' => $term->slug ) );

					if ( is_wp_error( $new_term_data ) ) {
						// $this->log( $new_term_data->get_error_message(), 'warning' );
						// $this->add_notice( $new_term_data->get_error_message(), 'warning' );

						continue;
					}

					$tax_terms[] = $new_term_data['term_id'];
				}
			}

			if ( !empty($mapped_term_ids) ) {
				foreach ( $mapped_term_ids as $term_id ) {
					$tax_terms[] = $term_id;
				}
			}
		}			

		$set_term_ids = wp_set_object_terms( $post_id, $tax_terms, $obj->to );

		// TODO: remove old terms

		if ( is_wp_error( $set_term_ids ) ) {
			// $this->log( $set_term_ids->get_error_message(), 'warning' );
			// $this->add_notice( $set_term_ids->get_error_message(), 'warning' );
		}

		// $this->log( "Copied terms from `$obj->from` to `$obj->to`.", 'success' );
		// $this->add_notice( "Copied terms from `$obj->from` to `$obj->to`.", 'success' );
	}    

}