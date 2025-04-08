<?php
/**
 * Setups the base for Taxonomy CLI commands
 *
 * @package     erikdmitchell\bcmigration\Abstracts
 * @since     0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\abstracts;

use erikdmitchell\bcmigration\traits\LoggerTrait;
use WP_Error;

/**
 * CLI Commands
 */
abstract class TaxonomyCLICommands extends CLICommands {

    use LoggerTrait;

    public function validate_post_type( string $post_type ) {
        if ( ! is_string( $post_type ) ) {
            $message = isset( $row_num )
                ? "Row {$row_num}: Post type must be a string. Skipping."
                : 'Post type must be a string.';

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
                : 'Taxonomy must be a string.';

            return new WP_Error( 'invalid_taxonomy', $message );
        }

        if ( ! taxonomy_exists( $taxonomy ) ) {
            $message = isset( $row_num )
                ? "Row {$row_num}: Taxonomy '{$taxonomy}' does not exist. Skipping."
                : "Taxonomy '{$taxonomy}' does not exist.";

            // log?

            return new WP_Error( 'invalid_taxonomy', $message );
        }

        return $taxonomy;
    }

    public function invalid_taxonomy( $taxonomy, $row_num = null ) {
        if ( ! is_wp_error( $taxonomy ) ) {
            return;
        }

        $this->log( "[SKIPPED] [IT] {$taxonomy->get_error_message()}" );

        if ( isset( $row_num ) ) {
            $this->add_notice( $taxonomy->get_error_message(), 'warning' );

            return false;
        } else {
            $this->add_notice( $taxonomy->get_error_message(), 'error' );
        }
    }

    // Neither Delete.php or Merge.php use params other than $file
    protected function process_csv( $file, $delete_old = false, $dry_run = false, $log = null ) { // TODO: do we need to pas $log.
        $rows     = array_map( 'str_getcsv', file( $file ) );
        $headers  = array_map( 'trim', array_shift( $rows ) );

        // Custom
        if (!$this->validate_headers( $headers, array( 'taxonomy', 'from_terms', 'to_term' ) )) {
            return;
        }

        foreach ( $rows as $i => $row ) {
            $row_num   = $i + 2;
            $data      = array_combine( $headers, $row );
            $data      = array_map( 'trim', $data );
            $post_type = $data['post_type'] ?? 'post'; // Custom
            $taxonomy  = $data['taxonomy'];

            // skip empty lines.
            if ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) {
                continue;
            }

            // Check required fields. Custom
            if ( ! $this->has_required_fields( $data, array( 'taxonomy', 'from_terms', 'to_term' ), $row_num ) ) {
                continue;
            }

            $taxonomy = $this->validate_taxonomy( $taxonomy );

            if ( is_wp_error( $taxonomy ) ) {
                $this->invalid_taxonomy( $taxonomy, $row_num );

                continue;
            }

            $from_terms = explode( '|', $data['from_terms'] ); // Custom
            $to_term    = $data['to_term']; // Custom

            // Custom
            if ( $dry_run ) {
                $this->dry_run_result( $taxonomy, $from_terms, $to_term, $row_num );

                continue;
            }

            // FIXME: this probably needs to be dynamic
            $result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $log, $row_num, $post_type );

            if ( is_wp_error( $result ) ) {
                $this->add_notice( "Row $row_num: Error - " . $result->get_error_message(), 'warning' );
            }
        }

        $this->add_notice( $dry_run ? 'Dry run complete.' : 'Batch merge complete.', 'success' );

        return;
    }

    protected function validate_headers(array $headers, array $required) {
        $missing = array_diff( $required, $headers );

        if ( ! empty( $missing ) ) {
            $this->add_notice( 'CSV is missing required columns: ' . implode( ', ', $missing ), 'error' );

            $this->log( 'CSV is missing required columns: ' . implode( ', ', $missing ) );

            return false;
        }

        return true;
    }

    protected function has_required_fields(array $data, array $required, int $row_num = 0) {
        $taxonomy   = $data['taxonomy']; // Custom
        $from_terms = explode( '|', $data['from_terms'] ); // Custom
        $to_term    = $data['to_term']; // Custom

        if ( ! $taxonomy || ! $from_terms || ! $to_term ) { // Custom
            $this->add_notice( "Row $row_num: Skipped - one or more required fields missing.", 'error' );

            $this->log( "Row $row_num: Skipped - one or more required fields missing." );

            return false;
        }        

        return true;
    }

    protected function dry_run_result($taxonomy, $from_terms, $to_term, $row_num) {
        $message = "Row $row_num: [DRY RUN] Would merge " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)";

        $this->log( $message );

        $this->add_notice( $message );

        return;
    }

    protected function validate_command_args( array $args, int $min_args = 0, int $max_args = 0 ): bool {
        $arg_count = count( $args );

        if ( $arg_count < $min_args || ( $max_args > 0 && $arg_count > $max_args ) ) {                     
            return false;
        }

        return true;
    }
}
