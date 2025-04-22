<?php
/**
 * Update Terms CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;

/**
 * UpdateTerms Class
 */
class UpdateTerms extends TaxonomyCLICommands {
    /**
     * Updates or creates taxonomy terms with parent-child relationships.
     *
     * ## OPTIONS
     *
     * [<taxonomy> <terms>]
     * : The taxonomy name, A string defining parent > child relationships.
     *
     * [--file=<file>]
     * : Path to a CSV file defining parent > children.
     *
     * [--dry-run]
     * : If set, no changes will be made.
     *
     * [--log=<logfile>]
     * : Path to a log file for results.
     *
     * ## EXAMPLES
     *
     *      wp boomi taxonomies update_terms content-type "News & Updates > Press Release, News"
     *      wp boomi taxonomies update_terms content-type "News & Updates > Press Release, News" --log=update-terms.log
     *      wp boomi taxonomies update_terms --file=terms.csv --dry-run
     *      wp boomi taxonomies update_terms --file=path/to/file.csv --log=log.txt
     *
     * @when after_wp_load
     *
     * @param string[]             $args       CLI positional arguments.
     * @param array<string, mixed> $assoc_args CLI associative arguments.
     *
     * @return void
     */
    public function update_terms( $args, $assoc_args ) {
        // list( $taxonomy ) = $args;
        $dry_run          = isset( $assoc_args['dry-run'] );
        // $csv_path         = isset( $assoc_args['csv'] ) ? $assoc_args['csv'] : null;
        $log_name         = $assoc_args['log'] ?? null;

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

        // if ( $csv_path ) {
        //     if ( ! file_exists( $csv_path ) ) {
        //         $this->add_notice( "CSV file not found: {$csv_path}", 'error' );
        //         $this->log( "CSV file not found: {$csv_path}" );
        //         $this->display_notices();

        //         return;
        //     }

        //     $this->process_csv( $csv_path, $taxonomy, $dry_run );
        // }        

        // Single merge.

        // $taxonomy = $this->validate_taxonomy( $taxonomy );

        // if ( is_wp_error( $taxonomy ) ) {
        //     $this->add_notice( $taxonomy->get_error_message(), 'error' );
        //     $this->log( $taxonomy->get_error_message() );
        //     $this->display_notices();

        //     return;
        // }

//  elseif ( isset( $args[1] ) ) {
            $this->process_single_term( $args, $dry_run );
        // } else {
        //     $this->add_notice( 'You must provide either a terms string or a CSV file.', 'error' );
        //     $this->log( 'You must provide either a terms string or a CSV file.' );
        // }

        $this->display_notices();
    }

    /**
     * Processes a CSV file containing parent > children relationships and updates
     * the terms in the specified taxonomy.
     *
     * @param string $file Path to the CSV file.
     * @param bool   $dry_run  If set, no changes will be made.
     *
     * @return void
     */
    private function process_csv( string $file, bool $dry_run ) {
        $mappings = array();
        // $lines    = file( $csv_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

        $rows    = array_map( 'str_getcsv', file( $file ) );
        $headers = array_map( 'trim', array_shift( $rows ) );

        if ( ! $this->validate_headers( $headers, array( 'taxonomy', 'from_terms', 'to_term' ) ) ) {
            return;
        }        

        foreach ( $lines as $line ) {
            $parts = explode( '>', $line );

            if ( count( $parts ) !== 2 ) {
                $this->add_notice( "Invalid format in line: {$line}", 'warning' );
                $this->log( "Invalid format in line: {$line}" );

                continue;
            }

            $parent     = trim( $parts[0] );
            $children   = array_map( 'trim', explode( ',', $parts[1] ) );
            $mappings[] = array(
                'parent'   => $parent,
                'children' => $children,
            );
        }

        if ( empty( $mappings ) ) {
            $this->add_notice( 'No valid mappings found in CSV file.', 'error' );
            $this->log( 'No valid mappings found in CSV file.' );

            $this->display_notices();

            return;
        }

        $this->process_terms( $mappings, $taxonomy, $dry_run );
    }

    private function process_single_term( array $args, bool $dry_run ) {
        $taxonomy = $this->validate_taxonomy( $args[0] );

        if ( is_wp_error( $taxonomy ) ) {
            $this->add_notice( $taxonomy->get_error_message(), 'error' );

            return;
        }

        if ( ! $this->validate_command_args( $args, 2,2 ) ) {
            $this->add_notice( 'Invalid arguments. Usage: wp taxonomy update_terms <taxonomy> <terms>', 'error' );

            return;
        } 
        
        $input = $args[1];
        $parts = explode( '>', $input );        
        
        if ( count( $parts ) === 2 ) {
            $parent     = trim( $parts[0] );
            $children   = array_map( 'trim', explode( ',', $parts[1] ) );
            $mappings[] = array(
                'parent'   => $parent,
                'children' => $children,
            );
        } else {
            $this->add_notice( 'Invalid input format. Use: Parent > Child1, Child2', 'error' );
            $this->log( 'Invalid input format. Use: Parent > Child1, Child2' );

            return;
        }    

        if ( empty( $mappings ) ) {
            $this->add_notice( 'No valid mappings found in input.', 'error' );
            $this->log( 'No valid mappings found in input.' );

            return;
        }
        
        $this->process_terms( $mappings, $taxonomy, $dry_run );
    }

    /**
     * Process a set of terms with parent-child relationships.
     *
     * @param array<array{parent: string, children: string[]}> $mappings Set of term sets with parent and children.
     * @param string                                           $taxonomy The taxonomy to update.
     * @param bool                                             $dry_run If set, no changes will be made.
     *
     * @return void
     */
    private function process_terms( $mappings, $taxonomy, $dry_run ) {
        foreach ( $mappings as $set ) {
            $parent   = $set['parent'];
            $children = $set['children'];

            $parent_term = term_exists( $parent, $taxonomy );

            if ( ! $parent_term ) {
                if ( $dry_run ) {
                    $this->add_notice( "Parent term does not exist: {$parent}", 'warning' );
                    $this->log( "Parent term does not exist: {$parent}" );

                    $parent_id = 0;
                } else {
                    $result = wp_insert_term( $parent, $taxonomy );

                    if ( is_wp_error( $result ) ) {
                        $this->add_notice( "Failed to create parent term '{$parent}': " . $result->get_error_message(), 'warning' );
                        $this->log( "Failed to create parent term '{$parent}': " . $result->get_error_message() );

                        continue;
                    }

                    $parent_id = $result['term_id'];

                    $this->add_notice( "Created parent term: {$parent}", 'success' );
                    $this->log( "Created parent term: {$parent}" );
                }
            } else {
                $parent_id = is_array( $parent_term ) ? $parent_term['term_id'] : $parent_term;

                $this->add_notice( "Parent term exists: {$parent} (ID {$parent_id})", 'success' );
                $this->log( "Parent term exists: {$parent} (ID {$parent_id})" );
            }

            foreach ( $children as $child ) {
                $child_term = term_exists( $child, $taxonomy );

                if ( ! $child_term ) {
                    if ( $dry_run ) {
                        $this->add_notice( "Child term does not exist: {$child}", 'warning' );
                        $this->log( "Child term does not exist: {$child}" );
                    } else {
                        $result = wp_insert_term( $child, $taxonomy, array( 'parent' => $parent_id ) );

                        if ( is_wp_error( $result ) ) {
                            $this->add_notice( "Failed to create child term '{$child}': " . $result->get_error_message(), 'warning' );
                            $this->log( "Failed to create child term '{$child}': " . $result->get_error_message() );
                        } else {
                            $this->add_notice( "Created child term: {$child} under {$parent}", 'success' );
                            $this->log( "Created child term: {$child} under {$parent}" );
                        }
                    }
                } else {
                    $child_id = is_array( $child_term ) ? $child_term['term_id'] : $child_term;

                    if ( $dry_run ) {
                        $this->add_notice( "Child term exists: {$child} (ID {$child_id})", 'success' );
                        $this->log( "Child term exists: {$child} (ID {$child_id})" );
                    } else {
                        wp_update_term( (int) $child_id, $taxonomy, array( 'parent' => $parent_id ) );

                        $this->add_notice( "Updated child term: {$child} to be under {$parent}", 'success' );
                        $this->log( "Updated child term: {$child} to be under {$parent}" );
                    }
                }
            }
        }
    }
}
