<?php
/**
 * Post Taxonomies mapped data class
 *
 * @package erikdmitchell\bcmigration\mapping
 * @since   0.3.0
 * @version 0.1.0
 */

 namespace erikdmitchell\bcmigration\mapping;

 class PostTaxonomiesMappedTerms {

    private $mapped_term_ids = array();

    private $unmapped_term_ids = array();

    private $custom_map = array();

    private $post_id = 0;

    private $from = '';

    private $to = '';

    public function __construct(string $from = '', string $to = '', int $post_id = 0, array $custom_map = array()) {
        $this->from = $from;
        $this->to = $to;
        $this->post_id = $post_id;
        $this->custom_map = $custom_map;

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

        $this->setup_term_ids();
    }

    public function get_mapped_term_ids() {
        return $this->mapped_term_ids;
    }

    public function get_unmapped_term_ids() {
        return $this->unmapped_term_ids;
    }

    public function get_custom_map() {
        return $this->custom_map;
    }

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