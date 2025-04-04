<?php
/**
 * Rename Taxonomies CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use WP_CLI;

class Rename extends CLICommands {

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
    public function rename_term($args, $assoc_args) {
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
    
            $required_columns = ['taxonomy', 'old_term', 'new_name'];
            $missing_columns = array_diff($required_columns, $header);
    
            if (! empty($missing_columns)) {
                WP_CLI::error('CSV is missing required columns: ' . implode(', ', $missing_columns));
            }
    
            foreach ($rows as $i => $row) {
                $row_num = $i + 2; // CSV line number
    
                if (count($row) === 1 && empty(trim($row[0]))) {
                    // skip empty lines
                    continue;
                }
    
                $data = array_combine($header, $row);
    
                // Trim all fields
                $data = array_map('trim', $data);
    
                $taxonomy  = $data['taxonomy'] ?? '';
                $old_term  = $data['old_term'] ?? '';
                $new_name  = $data['new_name'] ?? '';
                $new_slug  = $data['new_slug'] ?? null;
    
                if (! $taxonomy || ! $old_term || ! $new_name) {
                    $log("Row $row_num: Skipped – one or more required fields missing.");
    
                    continue;
                }
    
                if (! taxonomy_exists($taxonomy)) {
                    $log("Row $row_num: Skipped – taxonomy '$taxonomy' does not exist.");
    
                    continue;
                }
    
                $term = get_term_by('slug', sanitize_title($old_term), $taxonomy)
                    ?: get_term_by('name', $old_term, $taxonomy);
    
                if (! $term) {
                    $log("Row $row_num: Skipped – term '$old_term' not found in taxonomy '$taxonomy'.");
    
                    continue;
                }
    
                if ($dry_run) {
                    $log("Row $row_num: [DRY RUN] Would rename '$old_term' to '$new_name' in taxonomy '$taxonomy'");
                    
                    continue;
                }
    
                $result = $this->rename_taxonomy_term($taxonomy, $old_term, $new_name, $new_slug);
    
                if (is_wp_error($result)) {
                    $log("Row $row_num: Error – " . $result->get_error_message());
                } else {
                    $log("Row $row_num: Renamed '$old_term' to '$new_name' in taxonomy '$taxonomy'");
                }            
            }

            WP_CLI::success($dry_run ? "Dry run complete." : "Bulk rename complete.");

            return;
        }

        // Handle single rename.
        if (count($args) < 3) {
            WP_CLI::error('Please provide <taxonomy> <old_term> <new_name> or use --file=<file>');
        }

        list($taxonomy, $old_term, $new_name) = $args;
        $new_slug = $assoc_args['new-slug'] ?? null;

        if ($dry_run) {
            $log("[DRY RUN] Would rename '$old_term' to '$new_name' in taxonomy '$taxonomy'");

            return;
        }

        $result = $this->rename_taxonomy_term($taxonomy, $old_term, $new_name, $new_slug);

        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        } else {
            $message = "Renamed term '$old_term' to '$new_name' in taxonomy '$taxonomy'.";
            $log($message);

            WP_CLI::success($message);
        }
    }

    private function rename_taxonomy_term($taxonomy, $old_term, $new_name, $new_slug = null) {
        $term = get_term_by( 'slug', sanitize_title( $old_term ), $taxonomy );
        if ( ! $term ) {
            $term = get_term_by( 'name', $old_term, $taxonomy );
        }

        if ( ! $term || is_wp_error( $term ) ) {
            return new WP_Error( 'term_not_found', "Term '$old_term' not found in taxonomy '$taxonomy'." );
        }

        $args = [ 'name' => $new_name ];
        if ( $new_slug ) {
            $args['slug'] = sanitize_title( $new_slug );
        }

        return wp_update_term( $term->term_id, $taxonomy, $args );
    }
}

// WP_CLI::add_command('taxonomy rename_term', 'Rename_Taxonomy_Term_Command');

/*
# dry run
wp taxonomy rename_term --file=terms.csv --dry-run

#log output
wp taxonomy rename_term --file=terms.csv --log=rename-terms.log

#rename single
wp taxonomy rename_term industries "M&A" "Mergers. & Acquisitions" --new-slug="mergers-acquisitions" --dry-run --log=rename.log
*/
/*
taxonomy,old_term,new_name,new_slug
industries,M&A,Mergers. & Acquisitions,mergers-acquisitions
category,News,Latest News,latest-news
*/
/*

wp boomi taxonomies rename industries "M&A" "Mergers. & Acquisitions" --dry-run --log=rename.log

*/