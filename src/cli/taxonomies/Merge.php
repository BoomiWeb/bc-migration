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
use WP_CLI;
use WP_Error;

class Merge extends CLICommands {

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
        $logfile    = $assoc_args['log'] ?? null;
        $log        = null;
        $post_type = $assoc_args['post-type'] ?? 'post';

        if ($logfile) {
            // Logging helper
            $log = function ($message) use ($logfile) {
                WP_CLI::log($message);

                if ($logfile) {       
                    file_put_contents(BCM_PATH . '/' . $logfile, $message . PHP_EOL, FILE_APPEND);
                }
            };
        }

        $post_type = $assoc_args['post-type'] ?? 'post';

        if ( ! post_type_exists( $post_type ) ) {
            WP_CLI::error( "Post type '{$post_type}' does not exist. Please provide a valid post type." );
        }

        if ( isset( $assoc_args['file'] ) ) {
            $file = $assoc_args['file'];

            if ( ! file_exists( $file ) ) {
                WP_CLI::error( "CSV file not found: $file" );
            }

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

                if ( ! post_type_exists( $post_type ) ) {
                    $message = isset( $row_num )
                        ? "Row {$row_num}: Post type '{$post_type}' does not exist. Skipping."
                        : "Post type '{$post_type}' does not exist.";

                    WP_CLI::warning( $message );

                    if ( $log ) {
                        $log("[SKIPPED] $message");
                    }

                    if ( isset( $row_num ) ) { 
                        return false;
                    } else {
                        WP_CLI::error( $message );
                    }

                    continue;
                }

                $taxonomy   = $data['taxonomy'];
                $from_terms = explode( '|', $data['from_terms'] );
                $to_term    = $data['to_term'];

                // required fields see Delete.php

                if ( ! taxonomy_exists( $taxonomy ) ) {
                    $message = isset( $row_num )
                        ? "Row {$row_num}: Taxonomy '{$taxonomy}' does not exist. Skipping."
                        : "Taxonomy '{$taxonomy}' does not exist.";

                    WP_CLI::warning( $message );

                    if ( $log ) {
                        $log("[SKIPPED] $message");
                    }

                    if ( isset( $row_num ) ){ 
                        return false;
                    } else { 
                        WP_CLI::error( $message );
                    }
                }                

                if ( $dry_run ) {
                    $log( "Row $row_num: [DRY RUN] Would merge " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)" );
                    continue;
                }

                $result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $log, $row_num, $post_type );

                if ( is_wp_error( $result ) ) {
                    $log( "Row $row_num: Error – " . $result->get_error_message() );
                }
            }

            WP_CLI::success( $dry_run ? 'Dry run complete.' : 'Batch merge complete.' );

            return;
        }

        // Single command
        if ( count( $args ) < 3 ) {
            WP_CLI::error( 'Please provide <taxonomy> <from_terms> <to_term> or use --file=<file>' );
        }

        list( $taxonomy, $from_string, $to_term ) = $args;
        $from_terms = explode( '|', $from_string );

        if ( ! post_type_exists( $post_type ) ) {
            $message = isset( $row_num )
                ? "Row {$row_num}: Post type '{$post_type}' does not exist. Skipping."
                : "Post type '{$post_type}' does not exist.";
            
            WP_CLI::warning( $message );
            
            if ( $log ) {
                $log("[SKIPPED] $message");
            }

            if ( isset( $row_num ) ){ 
                return false;
            } else { 
                WP_CLI::error( $message );
            }
        }

        if ( ! taxonomy_exists( $taxonomy ) ) {
            $message = isset( $row_num )
                ? "Row {$row_num}: Taxonomy '{$taxonomy}' does not exist. Skipping."
                : "Taxonomy '{$taxonomy}' does not exist.";

            WP_CLI::warning( $message );

            if ( $log ) {
                $log("[SKIPPED] $message");
            }

            if ( isset( $row_num ) ){ 
                return false;
            } else { 
                WP_CLI::error( $message );
            }
        }        

        if ( $dry_run ) {
            if ( $log ) {
                $log( "[DRY RUN] Would merge " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)" );
            } else {
                WP_CLI::log( "[DRY RUN] Would merge " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)" );
            }

            return;
        }

        $result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $log, null, $post_type );

        if ( is_wp_error( $result ) ) {
            WP_CLI::error( $result->get_error_message() );
        }

        WP_CLI::success( 'Merge complete.' );
    }

    private function merge( $taxonomy, $from_terms, $to_term_name, $delete_old, $log, $row_num = null, $post_type = 'post' ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return new WP_Error( 'invalid_taxonomy', "Taxonomy '$taxonomy' does not exist." );
        }

        $to_term = get_term_by( 'name', $to_term_name, $taxonomy );

        if ( ! $to_term || is_wp_error( $to_term ) ) {
            $message = "Row {$row_num}: Target term '{$to_term_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";
            WP_CLI::warning( $message );

            if ( $log ) {
                $log("[SKIPPED] $message");
            }
            
            return false;
        }        

        foreach ( $from_terms as $from_name ) {
            $from_term = get_term_by( 'name', trim( $from_name ), $taxonomy );

            if ( ! $from_term || is_wp_error( $from_term ) ) {
                $message = "Row {$row_num}: From term '{$from_name}' does not exist in taxonomy '{$taxonomy}'. Skipping.";

                WP_CLI::warning( $message );

                if ( $log ) {
                    $log("[SKIPPED] $message");
                }

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
                if ($log) {
                    $log( ($row_num ? "Row $row_num: " : '') . "No posts found for '$from_name' in '$taxonomy'" );
                }
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
                    if ($log) {
                        $log( ($row_num ? "Row $row_num: " : '') . "Deleted term '$from_name'" );
                    }
                } else {
                    if ($log) {
                        $log( ($row_num ? "Row $row_num: " : '') . "Failed to delete term '$from_name'" );
                    }
                }
            } else {
                if ($log) {
                    $log( ($row_num ? "Row $row_num: " : '') . "Merged term '$from_name'" );
                }
            }
        }

        return true;
    }
}
