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
 
     public function migrate( string $from, string $to, int $post_id ) {
         $result = $this->migrate_post_terms( $from, $to, $post_id );
// echo "MigratePostTaxonomies::migrate\n";         
// print_r($result);         
     }

    public function get_mapped_term_id( string $from, string $to ) {
        
    }
 
     private function migrate_post_terms( string $from, string $to, int $post_id ) {
// echo "MigratePostTaxonomies::migrate_post_terms\n";
        if ( ! taxonomy_exists( $from ) ) {
            return new \WP_Error( 'invalid_from_taxonomy', "Taxonomy `$from` does not exist." );
         }
 
         if ( ! taxonomy_exists( $to ) ) {
             return new \WP_Error( 'invalid_to_taxonomy', "Taxonomy `$to` does not exist." );
         }
 
         $terms = wp_get_object_terms( $post_id, $from, [ 'fields' => 'ids' ] );
 
         if ( is_wp_error( $terms ) ) {
             return $terms;
         }
 
         foreach ( $terms as $term ) {
            $this->migrate_term( $term, $from, $to, $post_id );
         }
     }
 
     private function migrate_term( int $term_id, string $from, string $to, int $post_id ) {
echo "MigratePostTaxonomies::migrate_term\n";    
        if ( ! $term_id || ! $from || ! $to ) {
			return new \WP_Error( 'invalid_arguments', "Invalid arguments for migrate_term(): term_id: $term_id, from: $from, to: $to" );
        }
        
        $from_term_obj = get_term_by( 'id', $term_id, $from );

        if ( ! $from_term_obj ) {
            return new \WP_Error( 'term_not_found', "Term '$term_id' not found in taxonomy '$from'." );
        }

        if ( term_exists( $from_term_obj->name, $to ) ) {
            $to_term_obj = get_term_by( 'slug', $from_term_obj->slug, $to );

echo "term exists ($from_term_obj->term_id) - we need to update the post ($post_id) with the new term [$to] ($to_term_obj->term_id)\n";      
$result = wp_set_object_terms( $post_id, array( (int) $to_term_obj->term_id ), $to ); // this needs to happen in bulk     
print_r($result); 
            // return new \WP_Error( 'term_exists', "Term '{$from_term_obj->name}' already exists in taxonomy '{$to}'. Skipping." );
        }

        // $to_term_obj = get_term_by( 'slug', $from_term_obj->slug, $to );

         /*
		if (term_exists( $term, $to_tax ) ) {
			$this->log( "Term '$term' already exists in taxonomy '$to_tax'." );
			$this->add_notice( "Term '$term' already exists in taxonomy '$to_tax'." );
// do shit here
			return;
		}


		// term_exists( $from_term_obj->name, $to_tax )
		$to_term_obj = get_term_by( 'slug', $from_term_obj->slug, $to_tax );

		if ( $to_term_obj ) {
		}

        $result = wp_insert_term( $from_term_obj->name, $to_tax, [
            'slug'        => $from_term_obj->slug,
            'description' => $from_term_obj->description,
        ] );

        if ( is_wp_error( $result ) ) {

        } else {
echo "insert term - we need to update the post with the new term\n";			
// echo "insert term was good, we need to set the post with the updated term";			
			wp_set_object_terms( $post_id, $term, $to_tax );

        }
        */         
     }
}