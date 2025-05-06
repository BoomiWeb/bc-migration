<?php
/**
 * Map Post Taxonomies class
 *
 * @package erikdmitchell\bcmigration
 * @since   0.3.0
 * @version 0.1.0
 */

 namespace erikdmitchell\bcmigration;

 class MapPostTaxonomies {
 
    public function get_mapped_term_ids( string $from = '', string $to = '', int $post_id = 0 ) {
        if ( ! $from || ! $to ) {
            return new \WP_Error( 'invalid_arguments', "Invalid arguments for get_mapped_term_id(): from: $from, to: $to" );
        }

        if ( ! taxonomy_exists( $from ) ) {
            return new \WP_Error( 'invalid_from_taxonomy', "Taxonomy `$from` does not exist." );
         }
 
         if ( ! taxonomy_exists( $to ) ) {
             return new \WP_Error( 'invalid_to_taxonomy', "Taxonomy `$to` does not exist." );
         }

         if ( ! $post_id ) {
             return new \WP_Error( 'invalid_post_id', "Invalid post ID: $post_id" );
         }        

        $mapped_terms = array();
        $terms = wp_get_object_terms( $post_id, $from, array( 'fields' => 'ids' ) );

        if ( is_wp_error( $terms ) ) {
            return $terms;
        }

        foreach ( $terms as $term_id ) {          
            $mapped_term_id = $this->mapped_term_exists( $term_id, $from, $to );

            if ( is_wp_error( $mapped_term_id ) ) {
                continue;
            }
            
            $mapped_terms[] = $mapped_term_id;
        }

        return $mapped_terms;
    }

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