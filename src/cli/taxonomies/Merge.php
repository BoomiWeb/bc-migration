<?php
/**
 * Merge Taxonomies CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use erikdmitchell\bcmigration\traits\LoggerTrait;
use erikdmitchell\bcmigration\traits\TaxonomyTrait;
use WP_CLI;
use WP_Error;

class Merge extends CLICommands {

    use TaxonomyTrait;
    use LoggerTrait;

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
        $log_name    = $assoc_args['log'] ?? null;
        $post_type = $assoc_args['post-type'] ?? 'post';

        if ( $log_name ) {
            $this->set_log_name( $log_name );
        }

        $post_type = $this->validate_post_type( $post_type );

        if ( is_wp_error( $post_type ) ) {
            WP_CLI::error( $post_type->get_error_message() );

            $this->log("[SKIPPED] {$post_type->get_error_message()}");
        }

        // Batch merge
        if ( isset( $assoc_args['file'] ) ) {
            $file = $assoc_args['file'];

            if ( ! file_exists( $file ) ) {
                WP_CLI::error( "CSV file not found: $file" );

                $this->log("[SKIPPED] CSV file not found: $file");

                return;
            }             

            $this->process_csv( $file );

            return;
        }

        // Single command
        if ( count( $args ) < 3 ) {
            WP_CLI::error( 'Please provide <taxonomy> <from_terms> <to_term> or use --file=<file>' );
        }

        list( $taxonomy, $from_string, $to_term ) = $args;
        $from_terms = explode( '|', $from_string );

        $taxonomy   = $this->validate_taxonomy( $taxonomy );

        if ( is_wp_error( $taxonomy ) ) {
            WP_CLI::error( $taxonomy->get_error_message() );

            $this->log("[SKIPPED] {$taxonomy->get_error_message()}");
        }       

        if ( $dry_run ) {
            $message = "[DRY RUN] Would merge " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)";

            $this->log($message);
        
            WP_CLI::log( $message );

            return;
        }

        $result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $log, null, $post_type );

        if ( is_wp_error( $result ) ) {
            WP_CLI::error( $result->get_error_message() );
        }

        WP_CLI::success( 'Merge complete.' );
    }

    private function process_csv( $file, $delete_old = false, $dry_run = false, $log = null ) {
        $rows = array_map( 'str_getcsv', file( $file ) );
        $header = array_map( 'trim', array_shift( $rows ) );
        $required = [ 'taxonomy', 'from_terms', 'to_term' ];

        $missing = array_diff( $required, $header );

        if ( ! empty( $missing ) ) {
            WP_CLI::error( 'CSV is missing required columns: ' . implode( ', ', $missing ) );
        }

        foreach ( $rows as $i => $row ) {
            $row_num = $i + 2;
            $data = array_combine( $header, $row );
            $data = array_map( 'trim', $data );
            $post_type = $data['post_type'] ?? 'post';

            // count see Delete.php

            $taxonomy   = $data['taxonomy'];
            $from_terms = explode( '|', $data['from_terms'] );
            $to_term    = $data['to_term'];

            // required fields see Delete.php

            $taxonomy   = $this->validate_taxonomy( $taxonomy );

            if ( is_wp_error( $taxonomy ) ) {
                $this->output( $taxonomy->get_error_message(), 'warning' );
    
                $this->log("[SKIPPED] {$taxonomy->get_error_message()}");

                if ( isset( $row_num ) ) { 
                    return false;
                } else { 
                    $this->output( $taxonomy->get_error_message(), 'error' );
                }                
            }               

            if ( $dry_run ) {
                $message = "Row $row_num: [DRY RUN] Would merge " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)";

                $this->log( $message );

                WP_CLI::log( $message );

                continue;
            }

            $result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $log, $row_num, $post_type );

            if ( is_wp_error( $result ) ) {
                $this->log( "Row $row_num: Error - " . $result->get_error_message() );
            }
        }

        WP_CLI::success( $dry_run ? 'Dry run complete.' : 'Batch merge complete.' );

        return;        
    }

    private function merge( $taxonomy, $from_terms, $to_term_name, $delete_old, $log, $row_num = null, $post_type = 'post' ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return new WP_Error( 'invalid_taxonomy', "Taxonomy '$taxonomy' does not exist." );
        }

        $to_term = get_term_by( 'name', $to_term_name, $taxonomy );

        if ( ! $to_term || is_wp_error( $to_term ) ) {
            if ( $row_num ) {
                $message = "Row {$row_num}: Target term '{$to_term_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";    
            } else {
                $message = "Target term '{$to_term_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";
            }
            
            WP_CLI::warning( $message );

            $this->log("[SKIPPED] $message");
            
            return false;
        }        

        foreach ( $from_terms as $from_name ) {
            $from_term = get_term_by( 'name', trim( $from_name ), $taxonomy );

            if ( ! $from_term || is_wp_error( $from_term ) ) {
                $message = "Row {$row_num}: From term '{$from_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";

                WP_CLI::warning( $message );

                $this->log("[SKIPPED] $message");

                continue;
            }            

            // Get all posts with this term.
            $posts = get_posts( [
                'post_type'      => $post_type,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'tax_query'      => [
                    [
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => $from_term->term_id,
                    ],
                ],
                'fields' => 'ids',
            ] );

            if ( empty( $posts ) ) {
                $message = ($row_num ? "Row $row_num: " : '') . "No posts found for '$from_name' in '$taxonomy'";

                $this->log( $message );

                WP_CLI::warning( $message );
            } else {
                foreach ( $posts as $post_id ) {
                    $terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );
            
                    if ( ! in_array( $to_term->term_id, $terms, true ) ) {
                        $terms[] = $to_term->term_id;

                        wp_set_post_terms( $post_id, [ $to_term->term_id ], $taxonomy, true );
                    }
                }
            }
            
            if ( $delete_old ) {
                if ( ! is_wp_error( wp_delete_term( $from_term->term_id, $taxonomy ) ) ) {
                    $message = ($row_num ? "Row $row_num: " : '') . "Deleted term '$from_name'";

                    $this->log( $message );

                    WP_CLI::success( $message );
                } else {
                    $message = ($row_num ? "Row $row_num: " : '') . "Failed to delete term '$from_name'";

                    $this->log( $message );

                    WP_CLI::warning( $message );
                }
            } else {
                $message = ($row_num ? "Row $row_num: " : '') . "Merged term '$from_name'";

                $this->log( $message );

                WP_CLI::success( $message );
            }
        }

        return true;
    }
}
