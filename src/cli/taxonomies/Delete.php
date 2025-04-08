<?php
/**
 * Delete a Taxonomies Terms CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;
use WP_Error;

class Delete extends TaxonomyCLICommands {

    /**
     * Delete a single term or bulk terms via a CSV file.
     *
     * ## OPTIONS
     *
     * [<taxonomy> <term>]
     * : Taxonomy and term to delete.
     *
     * [--file=<file>]
     * : Path to CSV file for bulk delete.
     *
     * [--dry-run]
     * : Only simulate the changes; no actual updates.
     *
     * [--log=<logfile>]
     * : File to write logs to.
     *
     * ## EXAMPLES
     *
     *     wp taxonomy delete_term industries "M&A" "Mergers. & Acquisitions"
     *     wp taxonomy delete_term --file=terms.csv --dry-run --log=delete-log.txt
     *
     * @when after_wp_load
     */
    public function delete_terms( $args, $assoc_args ) {
        $dry_run = isset( $assoc_args['dry-run'] );
        $log_name   = $assoc_args['log'] ?? null;

        if ( $log_name ) {
            $this->set_log_name( $log_name );
        }

        // Batch merge.
        if ( isset( $assoc_args['file'] ) ) {
            if ( is_valid_file( $assoc_args['file'] ) ) {
                $this->process_csv( $assoc_args['file'] );
            }

            $this->display_notices();

            return;
        }        
/*

            $required_columns = array( 'taxonomy', 'term' );
            $missing_columns  = array_diff( $required_columns, $header );


            foreach ( $rows as $i => $row ) {

                $taxonomy   = $data['taxonomy'] ?? '';
                $term_names = explode( '|', $data['term'] );


                Validate taxonomy.

                $this->process_single( $dry_run, $delete_old, $post_type, $args );

                $this->display_notices();
        
                return;
                // Check term func
                // Check term
                // $term = get_term_by('slug', sanitize_title($term_name), $taxonomy)
                // ?: get_term_by('name', $term_name, $taxonomy);

                // if (! $term) {
                // $log("Row $row_num: Skipped – term '$term_name' not found in taxonomy '$taxonomy'.");

                // continue;
                // 
                // Check term [end]
                if ( $dry_run ) {
                    $log( "Row $row_num: [DRY RUN] Would delete term(s) " . implode( ', ', $term_names ) . " in $taxonomy" );

                    continue;
                }
// standard from here
                $result = $this->delete_taxonomy_term( $taxonomy, $term_names, $log, $row_num );

                if ( is_wp_error( $result ) ) {
                    $log( "Row $row_num: Error – " . $result->get_error_message() );
                }
                // } else {
                // $log("Row $row_num: Deleted term '$term_name' in taxonomy '$taxonomy'.");
                // }
            }

            WP_CLI::success( $dry_run ? 'Dry run complete.' : 'Bulk delete complete.' );

            return;
        */

        // Single command.
        $this->process_single_term( $args, $dry_run );

        $this->display_notices();

        return;
    }

    private function process_single_term( array $args, $dry_run ) {
        $taxonomy = $args[0] ?? '';
        $term_names = explode( '|', $args[1] ?? '' );

        if (!$this->validate_command_args( $args, 2, 2 )) {
            $this->add_notice( 'Invalid arguments. Usage: wp taxonomy delete_term <taxonomy> <term>', 'error' );

            return;
        }

        $taxonomy = $this->validate_taxonomy( $taxonomy );

        if ( is_wp_error( $taxonomy ) ) {
            $this->add_notice( $taxonomy->get_error_message(), 'error' );

            $this->log( "[SKIPPED] {$taxonomy->get_error_message()}" );

            return;
        }

        if ( $dry_run ) {
            $message = "[DRY RUN] Would delete term(s) '" . implode( ', ', $term_names ) . "' in taxonomy '$taxonomy'.";

            $this->log( $message );

            $this->add_notice( $message );

            return;
        }

        $result = $this->delete_taxonomy_term( $taxonomy, $term_names );

        if ( is_wp_error( $result ) ) {
            $this->add_notice( 'Error - ' . $result->get_error_message(), 'warning' );
        } else {
            $message = "Deleted term(s) '" . implode( ', ', $term_names ) . "' in taxonomy '$taxonomy'.";

            $this->add_notice( $message, 'success' );
            $this->log( $message );
        }

        $this->display_notices();
    }

    private function delete_taxonomy_term( $taxonomy, $term_names, $row_num = null ) {
        foreach ( $term_names as $term_name ) {
            $term = get_term_by( 'slug', sanitize_title( $term_name ), $taxonomy );

            if ( ! $term ) {
                $term = get_term_by( 'name', $term_name, $taxonomy );
            }

            if ( ! $term || is_wp_error( $term ) ) {
                $message = "Term '$term_name' not found in taxonomy '$taxonomy'.";

                $this->add_notice( $message, 'warning' );

                $this->log( "[SKIPPED] $message" );

                continue;
            }

            if ( ! is_wp_error( wp_delete_term( $term->term_id, $taxonomy ) ) ) {
                    $this->log( ( $row_num ? "Row $row_num: " : '' ) . "Deleted term '$term_name'" );
            } else {
                    $this->log( ( $row_num ? "Row $row_num: " : '' ) . "Failed to delete term '$term_name'" );
            }
        }
    }
}
