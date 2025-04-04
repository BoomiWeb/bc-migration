<?php
/**
 * Delete a Taxonomies Terms CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use WP_CLI;
use WP_Error;

class Delete extends CLICommands {

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
    public function delete_terms($args, $assoc_args) {
        $dry_run = isset($assoc_args['dry-run']);
        $logfile = $assoc_args['log'] ?? null;

        // Logging helper
        $log = function ($message) use ($logfile) {
            WP_CLI::log($message);

            if ($logfile) {       
                file_put_contents(BCM_PATH . '/' . $logfile, $message . PHP_EOL, FILE_APPEND);
            }
        };

        if (isset($assoc_args['file'])) {
            $file = $assoc_args['file'];

            if (! file_exists($file)) {
                WP_CLI::error("File not found: $file");
            }

            $rows = array_map('str_getcsv', file($file));
            $header = array_map('trim', array_shift($rows));
    
            $required_columns = ['taxonomy', 'term'];
            $missing_columns = array_diff($required_columns, $header);
    
            if (! empty($missing_columns)) {
                WP_CLI::error('CSV is missing required columns: ' . implode(', ', $missing_columns));
            }
    
            foreach ($rows as $i => $row) {
                $row_num = $i + 2; // CSV line number
                $data = array_combine($header, $row);
                $data = array_map('trim', $data);

                // post type see Merge.php

                if (count($row) === 1 && empty(trim($row[0]))) {
                    // skip empty lines
                    continue;
                }
    
                $taxonomy  = $data['taxonomy'] ?? '';
                $term_names = explode( '|', $data['term'] );
    
                // if (! $taxonomy || ! $term_name) {
                //     $log("Row $row_num: Skipped – one or more required fields missing.");
    
                //     continue;
                // }

                if ( ! taxonomy_exists( $taxonomy ) ) {
                    $message = isset( $row_num )
                        ? "Row {$row_num}: Taxonomy '{$taxonomy}' does not exist. Skipping."
                        : "Taxonomy '{$taxonomy}' does not exist.";

                    WP_CLI::warning( $message );

                    if ( $log ) {
                        $log("[SKIPPED] $message");
                    }

                    if ( isset( $row_num ) ) { 
                        return false;
                    } else { 
                        WP_CLI::error( $message );
                    }
                }                  
    
                // $term = get_term_by('slug', sanitize_title($term_name), $taxonomy)
                //     ?: get_term_by('name', $term_name, $taxonomy);
    
                // if (! $term) {
                //     $log("Row $row_num: Skipped – term '$term_name' not found in taxonomy '$taxonomy'.");
    
                //     continue;
                // }
    
                if ($dry_run) {
                    $log("Row $row_num: [DRY RUN] Would delete term(s) ". implode( ', ', $term_names ) . " in $taxonomy" );
                    
                    continue;
                }
    
                $result = $this->delete_taxonomy_term($taxonomy, $term_names, $log, $row_num);
    
                if (is_wp_error($result)) {
                    $log("Row $row_num: Error – " . $result->get_error_message());
                } else {
                    $log("Row $row_num: Deleted term '$term_name' in taxonomy '$taxonomy'.");
                }            
            }

            WP_CLI::success($dry_run ? "Dry run complete." : "Bulk delete complete.");

            return;
        }

        // Handle single delete.
        if (count($args) < 2) {
            WP_CLI::error('Please provide <taxonomy> <term> or use --file=<file>');
        }

        list($taxonomy, $term_name) = $args;
        $term_name = explode( '|', $term_name );

        if ($dry_run) {
            $log("[DRY RUN] Would delete term '$term_name' in taxonomy '$taxonomy'.");

            return;
        }

        $result = $this->delete_taxonomy_term($taxonomy, $term_name, $log);

        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            $message = "Deleted term '$term_name' in taxonomy '$taxonomy'.";
            $log($message);

            WP_CLI::success($message);
        }
    }

    private function delete_taxonomy_term($taxonomy, $term_names, $log, $row_num = null ) {
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return new WP_Error( 'invalid_taxonomy', "Taxonomy '$taxonomy' does not exist." );
        }

        foreach ( $term_names as $term_name ) {
            $term = get_term_by( 'slug', sanitize_title( $term_name ), $taxonomy );

            if ( ! $term ) {
                $term = get_term_by( 'name', $term_name, $taxonomy );
            }

            if ( ! $term || is_wp_error( $term ) ) {
                // return new WP_Error( 'term_not_found', "Term '$term_name' not found in taxonomy '$taxonomy'." );
                $message = "Term '$term_name' not found in taxonomy '$taxonomy'.";

                WP_CLI::warning( $message );

                if ( $log ) {
                    $log("[SKIPPED] $message");
                }                
            }

            if ( ! is_wp_error( wp_delete_term( $term->term_id, $taxonomy ) ) ) {
                if ($log) {
                    $log( ($row_num ? "Row $row_num: " : '') . "Deleted term '$term_name'" );
                }
            } else {
                if ($log) {
                    $log( ($row_num ? "Row $row_num: " : '') . "Failed to delete term '$term_name'" );
                }
            }
        }
    }
}
