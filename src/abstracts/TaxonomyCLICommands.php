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

    /**
     * Validates the given post type.
     *
     * Checks if the provided post type is a string and if it exists.
     * Returns a WP_Error if the validation fails.
     *
     * @param string $post_type The post type to validate.
     * @return string|WP_Error The post type if valid, or a WP_Error on failure.
     */
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

    /**
     * Validates the given taxonomy string.
     *
     * Checks if the provided taxonomy is a string and if it exists.
     * Returns a WP_Error if the taxonomy is invalid.
     *
     * @param string $taxonomy The taxonomy to validate.
     *
     * @return string|WP_Error Returns the taxonomy if valid, otherwise a WP_Error object.
     */
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

    /**
     * Handles invalid taxonomy errors by logging and adding notices.
     *
     * @param WP_Error $taxonomy The taxonomy error object.
     * @param int|null $row_num  Optional. The row number for logging purposes. Defaults to null.
     *
     * @return void|false Returns false if row number is provided, otherwise void.
     */
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

    /**
     * Validates that the given array of CSV headers contains all required fields.
     *
     * @param array $headers The array of CSV headers.
     * @param array $required The list of required field keys.
     *
     * @return bool Returns true if all required fields are present, false otherwise.
     */
    protected function validate_headers( array $headers, array $required ) {
        $missing = array_diff( $required, $headers );

        if ( ! empty( $missing ) ) {
            $this->add_notice( 'CSV is missing required columns: ' . implode( ', ', $missing ), 'error' );

            $this->log( 'CSV is missing required columns: ' . implode( ', ', $missing ) );

            return false;
        }

        return true;
    }

    /**
     * Checks if the given data array contains all required fields.
     *
     * @param array $data The data array to check.
     * @param array $required The list of required field keys.
     * @param int   $row_num The row number for logging purposes. Defaults to 0.
     *
     * @return bool Returns true if all required fields are present, false otherwise.
     */
    protected function has_required_fields( array $data, array $required, int $row_num = 0 ) {
        $missing_keys = array_diff_key( array_flip( $required ), $data );

        if ( ! empty( $missing_keys ) ) {
            // TODO: add message.
            $this->add_notice( "Row $row_num: Skipped - one or more required fields missing.", 'warning' );  // TODO: add check for row number.

            $this->log( "Row $row_num: Skipped - one or more required fields missing." );

            return false;
        }

        return true;
    }

    /**
     * Outputs a dry run message for a merge command.
     *
     * @param string $taxonomy The taxonomy.
     * @param array  $from_terms The terms to merge.
     * @param string $to_term The term to merge into.
     * @param int    $row_num The row number.
     */
    protected function dry_run_result( $taxonomy, $from_terms, $to_term, $row_num ) {
        // FIXME: this is too specific to the merge command.
        $message = "Row $row_num: [DRY RUN] Would merge " . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)";

        $this->log( $message );

        $this->add_notice( $message );

        return;
    }

    /**
     * Validate the number of command arguments.
     *
     * @param array $args      The command arguments.
     * @param int   $min_args  The minimum number of arguments allowed. Default 0.
     * @param int   $max_args  The maximum number of arguments allowed. Default 0.
     *
     * @return bool True if valid, false otherwise.
     */
    protected function validate_command_args( array $args, int $min_args = 0, int $max_args = 0 ): bool {
        $arg_count = count( $args );

        if ( $arg_count < $min_args || ( $max_args > 0 && $arg_count > $max_args ) ) {
            return false;
        }

        return true;
    }

    /**
     * Validates a term by name or slug within a given taxonomy.
     *
     * @param string $term_name The term name or slug.
     * @param string $taxonomy  The taxonomy to look in.
     * @param int    $row_num    The row number in the CSV file.
     *
     * @return bool|\WP_Term If the term is valid, returns the term object. Otherwise, returns false.
     */
    protected function is_term_valid( string $term_name, string $taxonomy, int $row_num = 0 ) {
        $term = get_term_by( 'slug', sanitize_title( $term_name ), $taxonomy ) ?: get_term_by( 'name', $term_name, $taxonomy );

        if ( ! $term || is_wp_error( $term ) ) {
            $message = "Row $row_num: Skipped - term '$term_name' not found in taxonomy '$taxonomy'."; // TODO: add check for row number

            $this->log( $message );

            $this->add_notice( $message, 'warning' );

            return false;
        }

        return $term;
    }
}
