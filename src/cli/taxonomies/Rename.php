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
use WP_Error;

/**
 * Rename Class
 */
class Rename extends TaxonomyCLICommands {

    /**
     * Rename a single term or bulk terms via a CSV file.
     *
     * ## OPTIONS
     *
     * [<taxonomy>]
     * : The taxonomy name.
     *
     * [<old_term>]
     * : The old term name or slug.
     * 
     * [<new_name>]
     * : The new name for the term.
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
     *     wp boomi taxonomies rename industries "M&A" "Mergers. & Acquisitions"
     *     wp boomi taxonomies rename --file=terms.csv --dry-run --log=rename-log.txt
     *
     * @param string[]             $args       CLI positional arguments.
     * @param array<string, mixed> $assoc_args CLI associative arguments.
     *
     * @return void
     */
    public function rename_term( $args, $assoc_args ) {
        $dry_run  = isset( $assoc_args['dry-run'] );
        $log_name = $assoc_args['log'] ?? null;

        if ( $log_name ) {
            $this->set_log_name( $log_name );
        }

        // Batch merge.
        if ( isset( $assoc_args['file'] ) ) {
            if ( is_valid_file( $assoc_args['file'] ) ) {
                $this->process_csv( $assoc_args['file'], $dry_run );
            }

            $this->display_notices();

            return;
        }

        // Handle single rename.
        $this->process_single_term( $args, $dry_run );

        $this->display_notices();
    }

    /**
     * Process a CSV file of terms to rename.
     *
     * @param string $file     Path to the CSV file.
     * @param bool   $dry_run  If set, simulate actions without making changes.
     *
     * @return void
     */
    private function process_csv( string $file, bool $dry_run = false ) {
        $rows    = array_map( 'str_getcsv', file( $file ) );
        $headers = array_map( 'trim', array_shift( $rows ) );

        if ( ! $this->validate_headers( $headers, array( 'taxonomy', 'old_term', 'new_name' ) ) ) {
            return;
        }

        foreach ( $rows as $i => $row ) {
            $row_num  = $i + 2;
            $data     = array_combine( $headers, $row );
            $data     = array_map( 'trim', $data );
            $taxonomy = $data['taxonomy'] ?? '';
            $old_term = $data['old_term'] ?? '';
            $new_name = $data['new_name'] ?? '';
            $new_slug = $data['new_slug'] ?? null;

            // skip empty lines.
            if ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) {
                continue;
            }

            // Check required fields.
            if ( ! $this->has_required_fields( $data, array( 'taxonomy', 'old_term', 'new_name' ), $row_num ) ) {
                continue;
            }

            $taxonomy = $this->validate_taxonomy( $taxonomy );

            if ( is_wp_error( $taxonomy ) ) {
                $this->invalid_taxonomy( $taxonomy, $row_num );

                continue;
            }

            if ( $dry_run ) {
                $message = "Row $row_num: [DRY RUN] Would rename '$old_term' to '$new_name' in taxonomy '$taxonomy'";

                $this->log( $message );

                $this->add_notice( $message );

                continue;
            }

            $result = $this->rename_taxonomy_term( $taxonomy, $old_term, $new_name, $new_slug );

            if ( is_wp_error( $result ) ) {
                $this->add_notice( "Row $row_num: Error - " . $result->get_error_message(), 'warning' );
            } else {
                $message = "Row $row_num: Renamed '$old_term' to '$new_name' in taxonomy '$taxonomy'";

                $this->add_notice( $message, 'success' );
                $this->log( $message );
            }
        }

        $this->add_notice( $dry_run ? 'Dry run complete.' : 'Batch merge complete.', 'success' );
    }

    /**
     * Processes the renaming of a single taxonomy term.
     *
     * @param string[]             $args       CLI arguments including taxonomy, old term name, and new term name.
     * @param array<string, mixed> $assoc_args CLI associative arguments.
     * @param bool                 $dry_run    If true, simulates the rename process without making changes.
     *
     * @return void
     */
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

    /**
     * Rename a taxonomy term.
     *
     * @param string $taxonomy The taxonomy to update.
     * @param string $old_term The old term name or slug.
     * @param string $new_name The new name for the term.
     * @param string $new_slug Optional new slug for the term.
     *
     * @return array{term_id: int, term_taxonomy_id: int}|WP_Error The updated term data or a WP_Error on failure.
     */
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
