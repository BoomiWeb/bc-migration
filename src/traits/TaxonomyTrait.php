<?php
/**
 * Taxonomy trait class
 *
 * @package erikdmitchell\bcmigration\traits
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\traits;

use WP_Error;

trait TaxonomyTrait {

    public function validate_post_type( string $post_type ) {
        if ( ! is_string( $post_type ) ) {            
            $message = isset( $row_num )
                ? "Row {$row_num}: Post type must be a string. Skipping."
                : "Post type must be a string.";

            return new WP_Error( 'invalid_post_type', $message );
        }

        if ( ! post_type_exists( $post_type ) ) {
            $message = isset( $row_num )
                ? "Row {$row_num}: Post type '{$post_type}' does not exist. Skipping."
                : "Post type '{$post_type}' does not exist.";

            return new WP_Error( 'invalid_post_type', $message );
        }

        return $post_type;
    }

    public function validate_taxonomy( string $taxonomy ) {
        if ( ! is_string( $taxonomy ) ) {            
            $message = isset( $row_num )
                ? "Row {$row_num}: Taxonomy must be a string. Skipping."
                : "Taxonomy must be a string.";

            return new WP_Error( 'invalid_taxonomy', $message );
        }

        if ( ! taxonomy_exists( $taxonomy ) ) {
            $message = isset( $row_num )
                ? "Row {$row_num}: Taxonomy '{$taxonomy}' does not exist. Skipping."
                : "Taxonomy '{$taxonomy}' does not exist.";

            return new WP_Error( 'invalid_taxonomy', $message );
        }

        return $taxonomy;
    }

}
