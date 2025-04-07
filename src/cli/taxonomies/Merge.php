<?php
/**
 * Merge Taxonomies CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;
use WP_Error;

class Merge extends TaxonomyCLICommands {

    /**
     * Merge terms within a taxonomy.
     *
     * ## OPTIONS
     *
     * [<taxonomy> <from_terms> <to_term>]
     * : Taxonomy, pipe-separated list of old terms, and destination term.
     *
     * [--file=<file>]
     * : Path to CSV file for batch merge.
     *
     * [--delete-old]
     * : Delete the old terms after merging.
     *
     * [--dry-run]
     * : Simulate actions without making changes.
     *
     * [--log=<logfile>]
     * : Path to a log file for results.
     *
     * ## EXAMPLES
     *
     *     wp taxonomy merge_terms products "B2B Integration|CRM Integration" "Integration" --delete-old
     *     wp taxonomy merge_terms --file=merge-terms.csv --dry-run --log=merge.log
     *
     * @when after_wp_load
     */
    public function merge_terms( $args, $assoc_args ) {
        $dry_run    = isset( $assoc_args['dry-run'] );
        $delete_old = isset( $assoc_args['delete-old'] );
        $log_name   = $assoc_args['log'] ?? null;
        $post_type  = $assoc_args['post-type'] ?? 'post';

        if ( $log_name ) {
            $this->set_log_name( $log_name );
        }

        $post_type = $this->validate_post_type( $post_type );

        if ( is_wp_error( $post_type ) ) {
            $this->add_notice( $post_type->get_error_message(), 'error' );

            $this->log( "[SKIPPED] {$post_type->get_error_message()}" );
        }

        // Batch merge.
        if ( isset( $assoc_args['file'] ) ) {
            if ( is_valid_file( $assoc_args['file'] ) ) {
                $this->process_csv( $assoc_args['file'] );
            }

            $this->display_notices();

            return;
        }

        // Single command.
        $this->process_single_term( $args, $dry_run, $delete_old, $post_type );
    }

    private function process_single_term( array $args, $dry_run, $delete_old, $post_type ) {       
        $taxonomy = $this->validate_taxonomy( $args[0] );

        if ( is_wp_error( $taxonomy ) ) {
            $this->add_notice( $taxonomy->get_error_message(), 'error' );

            return;
        }

        $from_terms = explode( '|', $args[1] );
        $to_term    = $args[2];

        if ( $dry_run ) {
            $message = "[DRY RUN] Would merge " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)";

            $this->log( $message );

            return;
        }

        $result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, null, $post_type );

        if ( is_wp_error( $result ) ) {
            $this->add_notice( 'Error - ' . $result->get_error_message(), 'warning' );
        } else {
            $this->log( "Merged " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)" );
            $this->add_notice( "Merged " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)", 'success' );
        }

        $this->display_notices();

        return;
    }

    protected function merge( $taxonomy, $from_terms, $to_term_name, $delete_old, $row_num = null, $post_type = 'post' ) {
        $to_term = get_term_by( 'name', $to_term_name, $taxonomy );

        if ( ! $to_term || is_wp_error( $to_term ) ) {
            if ( $row_num ) {
                $message = "Row {$row_num}: Target term '{$to_term_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";
            } else {
                $message = "Target term '{$to_term_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";
            }

            $this->add_notice( $message, 'warning' );

            $this->log( "[SKIPPED] $message" );

            return false;
        }

        foreach ( $from_terms as $from_name ) {
            $from_term = get_term_by( 'name', trim( $from_name ), $taxonomy );

            if ( ! $from_term || is_wp_error( $from_term ) ) {
                $message = ( $row_num ? "Row $row_num: " : '' ) . "From term '{$from_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";

                $this->add_notice( $message, 'warning' );

                $this->log( "[SKIPPED] $message" );

                continue;
            }

            // Get all posts with this term.
            $posts = get_posts(
                array(
                    'post_type'      => $post_type,
                    'post_status'    => 'any',
                    'posts_per_page' => -1,
                    'tax_query'      => array(
                        array(
                            'taxonomy' => $taxonomy,
                            'field'    => 'term_id',
                            'terms'    => $from_term->term_id,
                        ),
                    ),
                    'fields'         => 'ids',
                )
            );

            if ( empty( $posts ) ) {
                $message = ( $row_num ? "Row $row_num: " : '' ) . "No posts found for '$from_name' in '$taxonomy'";

                $this->log( $message );

                $this->add_notice( $message, 'warning' );
            } else {
                foreach ( $posts as $post_id ) {
                    $terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );

                    if ( ! in_array( $to_term->term_id, $terms, true ) ) {
                        $terms[] = $to_term->term_id;

                        wp_set_post_terms( $post_id, array( $to_term->term_id ), $taxonomy, true );
                    }
                }
            }

            if ( $delete_old ) { // TODO: we have a delete CLI class, maybe use that instead?
                if ( ! is_wp_error( wp_delete_term( $from_term->term_id, $taxonomy ) ) ) {
                    $message = ( $row_num ? "Row $row_num: " : '' ) . "Deleted term '$from_name'";

                    $this->log( $message );

                    $this->add_notice( $message, 'success' );
                } else {
                    $message = ( $row_num ? "Row $row_num: " : '' ) . "Failed to delete term '$from_name'";

                    $this->log( $message );

                    $this->add_notice( $message, 'warning' );
                }
            } else {
                $message = ( $row_num ? "Row $row_num: " : '' ) . "Merged term '$from_name'";

                $this->log( $message );

                $this->add_notice( $message, 'success' );
            }
        }

        return true;
    }
}
