<?php
/**
 * Migrate Post Taxonomies class
 *
 * @package erikdmitchell\bcmigration
 * @since   0.3.0
 * @version 0.1.0
 */

 namespace erikdmitchell\bcmigration;

 class MigratePostTaxonomies {
 
     public function migrate( string $from, string $to, int $post_id ) {
         $result = $this->migrate_post_terms( $from, $to, $post_id );
// echo "MigratePostTaxonomies::migrate\n";         
// print_r($result);         
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
echo "term_id: $term_id\n";
echo "from: $from\n";
echo "to: $to\n";
echo "post_id: $post_id\n";
         // Implement term migration logic.
     }
}