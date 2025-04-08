<?php
/**
 * Rename Taxonomies CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;
use WP_CLI;
use WP_Error;

class Rename extends TaxonomyCLICommands {

    /**
     * Rename a single term or bulk terms via a CSV file.
     *
     * ## OPTIONS
     *
     * [<taxonomy> <old_term> <new_name>]
     * : Taxonomy, old term, and new name for single term rename.
     *
     * [--new-slug=<new-slug>]
     * : Optional new slug for single rename.
     *
     * [--file=<file>]
     * : Path to CSV file for bulk rename.
     *
     * [--dry-run]
     * : Only simulate the changes; no actual updates.
     *
     * [--log=<logfile>]
     * : File to write logs to.
     *
     * ## EXAMPLES
     *
     *     wp taxonomy rename_term industries "M&A" "Mergers. & Acquisitions"
     *     wp taxonomy rename_term --file=terms.csv --dry-run --log=rename-log.txt
     *
     * @when after_wp_load
     */
    public function rename_term( $args, $assoc_args ) {
        $dry_run  = isset( $assoc_args['dry-run'] );
        $log_name = $assoc_args['log'] ?? null;

        if ( $log_name ) {
            $this->set_log_name( $log_name );
        }

        // Batch merge.
        /*
        if ( isset( $assoc_args['file'] ) ) {
            if ( is_valid_file( $assoc_args['file'] ) ) {
                $this->process_csv( $assoc_args['file'], $dry_run );
            }

            $this->display_notices();

            return;
        }
        */

        if ( isset( $assoc_args['file'] ) ) {
            $file = $assoc_args['file'];

            if ( ! file_exists( $file ) ) {
                WP_CLI::error( "File not found: $file" );
            }

            $rows   = array_map( 'str_getcsv', file( $file ) );
            $header = array_map( 'trim', array_shift( $rows ) );

            $required_columns = array( 'taxonomy', 'old_term', 'new_name' );
            $missing_columns  = array_diff( $required_columns, $header );

            if ( ! empty( $missing_columns ) ) {
                WP_CLI::error( 'CSV is missing required columns: ' . implode( ', ', $missing_columns ) );
            }

            foreach ( $rows as $i => $row ) {
                $row_num = $i + 2; // CSV line number

                if ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) {
                    // skip empty lines
                    continue;
                }

                $data = array_combine( $header, $row );

                // Trim all fields
                $data = array_map( 'trim', $data );

                $taxonomy = $data['taxonomy'] ?? '';
                $old_term = $data['old_term'] ?? '';
                $new_name = $data['new_name'] ?? '';
                $new_slug = $data['new_slug'] ?? null;

                if ( ! $taxonomy || ! $old_term || ! $new_name ) {
                    $log( "Row $row_num: Skipped – one or more required fields missing." );

                    continue;
                }

                if ( ! taxonomy_exists( $taxonomy ) ) {
                    $log( "Row $row_num: Skipped – taxonomy '$taxonomy' does not exist." );

                    continue;
                }

                $term = get_term_by( 'slug', sanitize_title( $old_term ), $taxonomy )
                    ?: get_term_by( 'name', $old_term, $taxonomy );

                if ( ! $term ) {
                    $log( "Row $row_num: Skipped – term '$old_term' not found in taxonomy '$taxonomy'." );

                    continue;
                }

                if ( $dry_run ) {
                    $log( "Row $row_num: [DRY RUN] Would rename '$old_term' to '$new_name' in taxonomy '$taxonomy'" );

                    continue;
                }

                $result = $this->rename_taxonomy_term( $taxonomy, $old_term, $new_name, $new_slug );

                if ( is_wp_error( $result ) ) {
                    $log( "Row $row_num: Error – " . $result->get_error_message() );
                } else {
                    $log( "Row $row_num: Renamed '$old_term' to '$new_name' in taxonomy '$taxonomy'" );
                }
            }

            WP_CLI::success( $dry_run ? 'Dry run complete.' : 'Bulk rename complete.' );

            return;
        }

        // Handle single rename.
        $this->process_single_term( $args, $dry_run );

        $this->display_notices();

        return;
    }

    private function process_single_term( array $args, $dry_run ) {
        $taxonomy = $this->validate_taxonomy( $args[0] );

        if ( is_wp_error( $taxonomy ) ) {
            $this->add_notice( $taxonomy->get_error_message(), 'error' );

            return;
        }

        if ( ! $this->validate_command_args( $args, 3, 3 ) ) {
            $this->add_notice( 'Please provide <taxonomy> <old_term> <new_name> or use --file=<file>', 'error' );

            return;
        }

        list($taxonomy, $old_term, $new_name) = $args;
        $new_slug                             = $assoc_args['new-slug'] ?? null;

        if ( $dry_run ) {
            $message = "[DRY RUN] Would rename '$old_term' to '$new_name' in taxonomy '$taxonomy'";

            $this->log( $message );

            $this->add_notice( $message );

            return;
        }

        $result = $this->rename_taxonomy_term( $taxonomy, $old_term, $new_name, $new_slug );

        if ( is_wp_error( $result ) ) {
            $this->add_notice( 'Error - ' . $result->get_error_message(), 'warning' );
        } else {
            $message = "Renamed term '$old_term' to '$new_name' in taxonomy '$taxonomy'.";

            $this->add_notice( $message, 'success' );
            $this->log( $message );
        }
    }    

    private function rename_taxonomy_term( $taxonomy, $old_term, $new_name, $new_slug = null ) {
        $term = $this->is_term_valid( $old_term, $taxonomy );

        if ( ! $term ) {
            return;
        }        

        $args = array( 'name' => $new_name );

        if ( $new_slug ) {
            $args['slug'] = sanitize_title( $new_slug );
        }

        return wp_update_term( $term->term_id, $taxonomy, $args );
    }
}
