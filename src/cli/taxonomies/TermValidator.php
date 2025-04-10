<?php
/**
 * Term Validator CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;

/**
 * TermValidator Class
 */
class TermValidator extends TaxonomyCLICommands {
    /**
     * Validate, sync, and optionally clean up taxonomy terms.
     *
     * ## OPTIONS
     *
     * <taxonomy>
     * : The taxonomy to validate and sync.
     *
     * [--terms=<terms>]
     * : Comma-separated list of term identifiers.
     *
     * [--file=<file>]
     * : Path to a CSV file with one term per line.
     *
     * [--field=<field>]
     * : Field type to match terms by. Accepts: name, slug, id. Default is name.
     *
     * [--delete]
     * : Delete terms not in the provided list.
     *
     * [--dry-run]
     * : Perform a dry run without modifying anything.
     *
     * [--log=<logfile>]
     * : Path to a log file for results.
     *
     * ## EXAMPLES
     *
     *     wp term-validator category --terms="News,Updates" --field=name
     *     wp term-validator category --file=slugs.csv --field=slug --delete
     *     wp term-validator category --file=slugs.csv --field=slug --delete --log=term-validation.log
     *     wp term-validator category --terms="123,456" --field=id --dry-run
     *
     * @param string[]             $args       CLI positional arguments.
     * @param array<string, mixed> $assoc_args CLI associative arguments.
     *
     * @return void
     */
    public function validate_terms( $args, $assoc_args ) {
        list( $taxonomy ) = $args;
        $field            = $assoc_args['field'] ?? 'name';
        $dry_run          = isset( $assoc_args['dry-run'] );
        $log_name         = $assoc_args['log'] ?? null;
        $terms_input      = array();

        if ( $log_name ) {
            $this->set_log_name( $log_name );
        }

        $taxonomy = $this->validate_taxonomy( $taxonomy );

        if ( is_wp_error( $taxonomy ) ) {
            $this->invalid_taxonomy( $taxonomy );

            return;
        }

        // Load term input from args.
        if ( isset( $assoc_args['terms'] ) ) {
            $terms_input = array_map( 'trim', explode( ',', $assoc_args['terms'] ) );
        } elseif ( isset( $assoc_args['file'] ) ) {
            $file = $assoc_args['file'];

            if ( ! file_exists( $file ) ) {
                $this->add_notice( "File '{$file}' not found.", 'error' );

                $this->log( "[SKIPPED] File '{$file}' not found." );

                $this->display_notices();

                return;
            }

            $lines       = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
            $terms_input = array_map( 'trim', $lines );
        } else {
            $this->add_notice( 'You must provide either --terms or --file.', 'error' );

            $this->log( '[SKIPPED] You must provide either --terms or --file.' );

            $this->display_notices();

            return;
        }

        // Normalize.
        $provided_terms = array_map( 'strval', $terms_input );

        // Get all existing terms in the taxonomy.
        $existing_terms = get_terms(
            array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            )
        );

        $existing_map = array(); // [field_value => term object].

        foreach ( $existing_terms as $term ) {
            $key                  = (string) $term->$field;
            $existing_map[ $key ] = $term;
        }

        $created = array();

        foreach ( $provided_terms as $term_identifier ) {
            if ( isset( $existing_map[ $term_identifier ] ) ) {
                continue; // Term already exists.
            }

            if ( $dry_run ) {
                $message = "[Dry Run] Would create term: {$term_identifier}";

                $this->log( $message );

                $this->add_notice( $message, 'info' );

                continue;
            }

            // Try to insert by name, fallback to slug or ID as best effort.
            $insert_args = array( 'slug' => sanitize_title( $term_identifier ) );

            if ( 'slug' === $field ) {
                $insert_args['slug'] = $term_identifier;
                $term_result         = wp_insert_term( $term_identifier, $taxonomy, $insert_args );
            } elseif ( 'id' === $field ) {
                $this->add_notice( "Cannot create terms by ID: {$term_identifier}", 'warning' );

                $this->log( "[SKIPPED] Cannot create terms by ID: {$term_identifier}" );

                continue;
            } else {
                $term_result = wp_insert_term( $term_identifier, $taxonomy );
            }

            if ( is_wp_error( $term_result ) ) {
                $this->add_notice( "Failed to create term '{$term_identifier}': " . $term_result->get_error_message(), 'warning' );
                $this->log( "[SKIPPED] Failed to create term '{$term_identifier}': " . $term_result->get_error_message() );
            } else {
                $this->add_notice( "Created term: {$term_identifier}", 'success' );
                $this->log( "Created term: {$term_identifier}" );

                $created[] = $term_identifier;
            }
        }

        if ( isset( $assoc_args['delete'] ) ) {
            $this->delete_terms( $taxonomy, $provided_terms, $existing_map, $field, $dry_run );
        }

        $this->add_notice( $dry_run ? 'Dry run complete.' : 'Batch validation complete.', 'success' );

        $this->display_notices();
    }

    /**
     * Deletes taxonomy terms not present in the provided list.
     *
     * @param string                  $taxonomy The taxonomy slug (e.g., 'category', 'post_tag', 'content-type').
     * @param string[]                $provided_terms The list of terms to keep; any existing terms not in this list will be deleted.
     * @param array<string, \WP_Term> $existing_map A map of existing terms by the specified field.
     * @param string                  $field The field to use for mapping existing terms (e.g., 'slug', 'name', 'id').
     * @param bool                    $dry_run If true, will not delete any terms; just log and display notices.
     * @return void
     */
    private function delete_terms( $taxonomy, $provided_terms, $existing_map, $field, $dry_run ) {
        $provided_lookup = array_flip( $provided_terms );
        $taxonomy_object = get_taxonomy( $taxonomy );
        $default_term_id = null;

        if ( 'category' === $taxonomy ) {
            $default_term_id = (int) get_option( 'default_category' );
        } else {
            $taxonomy_object = get_taxonomy( $taxonomy );
            if ( isset( $taxonomy_object->default_term ) ) {
                $default_term = get_term_by( 'slug', $taxonomy_object->default_term, $taxonomy );
                if ( $default_term && ! is_wp_error( $default_term ) ) {
                    $default_term_id = $default_term->term_id;
                }
            }
        }

        foreach ( $existing_map as $field_value => $term ) {
            if ( ! isset( $provided_lookup[ $field_value ] ) ) {
                if ( $dry_run ) {
                    $this->add_notice( "[Dry Run] Would delete term: {$term->name} ({$field}: {$field_value})", 'info' );
                    $this->log( "[Dry Run] Would delete term: {$term->name} ({$field}: {$field_value})" );

                    continue;
                }

                if ( $term->term_id === $default_term_id ) {
                    $this->add_notice( "Cannot delete default term: {$term->name} ({$field}: {$field_value})", 'warning' );
                    $this->log( "[SKIPPED] Cannot delete default term: {$term->name} ({$field}: {$field_value})" );

                    continue;
                }

                $deleted = wp_delete_term( $term->term_id, $taxonomy );

                if ( is_wp_error( $deleted ) ) {
                    $this->add_notice( "Failed to delete '{$term->name}': " . $deleted->get_error_message(), 'warning' );
                    $this->log( "[SKIPPED] Failed to delete '{$term->name}': " . $deleted->get_error_message() );
                } else {
                    $this->add_notice( "Deleted term: {$term->name} ({$field}: {$field_value})", 'success' );
                    $this->log( "Deleted term: {$term->name} ({$field}: {$field_value})" );
                }
            }
        }
    }
}
