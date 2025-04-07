<?php
/**
 * Setups the base for Taxonomy CLI commands
 *
 * @package     erikdmitchell\bcmigration\Abstracts
 * @since     0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\abstracts;

use WP_CLI;
use WP_Error;

/**
 * CLI Commands
 */
abstract class TaxonomyCLICommands extends CLICommands {

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

    public function invalid_taxonomy($taxonomy, $row_num = null) {
        if ( !is_wp_error( $taxonomy ) ) {
            return;
        }

        $this->add_notice( 'error', $taxonomy->get_error_message() );

        $this->log("[SKIPPED] {$taxonomy->get_error_message()}");

        if ( isset( $row_num ) ) { 
            return false;
        } else { 
            $this->add_notice( 'error', $taxonomy->get_error_message() );
        }
    }    

    protected function process_csv( $file, $delete_old = false, $dry_run = false, $log = null ) {
        $rows = array_map( 'str_getcsv', file( $file ) );
        $header = array_map( 'trim', array_shift( $rows ) );
        $required = [ 'taxonomy', 'from_terms', 'to_term' ];

        $missing = array_diff( $required, $header );

        if ( ! empty( $missing ) ) {
            $this->add_notice( 'error', 'CSV is missing required columns: ' . implode( ', ', $missing ) );
        }

        foreach ( $rows as $i => $row ) {
            $row_num = $i + 2;
            $data = array_combine( $header, $row );
            $data = array_map( 'trim', $data );
            $post_type = $data['post_type'] ?? 'post';

            // skip empty lines.
            if (count($row) === 1 && empty(trim($row[0]))) {
                continue;
            }

            $taxonomy   = $data['taxonomy'];
            $from_terms = explode( '|', $data['from_terms'] );
            $to_term    = $data['to_term'];

            // check required fields.
            if (! $taxonomy || ! $from_terms || ! $to_term) {
                $this->log("Row $row_num: Skipped - one or more required fields missing.");

                continue;
            }

            $taxonomy   = $this->validate_taxonomy( $taxonomy );

            if ( is_wp_error( $taxonomy ) ) {
                $this->invalid_taxonomy( $taxonomy, $row_num );

                continue;
            }               

            if ( $dry_run ) {
                $message = "Row $row_num: [DRY RUN] Would merge " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)";

                $this->log( $message );

                $this->add_notice( 'info', $message );

                continue;
            }

            // FIXME: this probably needs to be dynamic
            $result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $log, $row_num, $post_type );

            if ( is_wp_error( $result ) ) {
                $this->add_notice('warning', "Row $row_num: Error - " . $result->get_error_message() );
            }
        }

        $this->add_notice( 'success', $dry_run ? 'Dry run complete.' : 'Batch merge complete.' );

        return;        
    }

    protected function process_single(string $dry_run, string $delete_old, string $post_type, array $args = []) {
        WP_CLI::log( 'Processing single command...' );

        if ( count( $args ) < 3 ) {
            WP_CLI::error( 'Please provide <taxonomy> <from_terms> <to_term> or use --file=<file>' );
        }

        list( $taxonomy, $from_string, $to_term ) = $args;
        $from_terms = explode( '|', $from_string );

        $taxonomy = $this->validate_taxonomy( $taxonomy );

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

}