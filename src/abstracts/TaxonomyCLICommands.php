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
echo "has_required_fields - check\n";        
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

    protected function dry_run_result($taxonomy, $from_terms, $to_term, $row_num) { // FIXME: this is too specific to the merge command.
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

    protected function is_term_valid(string $term_name, string $taxonomy, int $row_num = 0) {
        $term = get_term_by('slug', sanitize_title($term_name), $taxonomy) ?: get_term_by('name', $term_name, $taxonomy);

        if ( ! $term || is_wp_error( $term ) ) {
            $message = "Row $row_num: Skipped - term '$term_name' not found in taxonomy '$taxonomy'."; // TODO: add check for row number

            $this->log( $message );

            $this->add_notice( $message, 'warning' );
            
            return false;
        }

        return $term;
    }
}
