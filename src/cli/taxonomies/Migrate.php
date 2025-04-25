<?php
/**
 * Migrate Taxonomies CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.1
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;
 
/**
 * Migrate Class
*/
class Migrate extends TaxonomyCLICommands {
    /**
     * Migrate terms between different Taxonomies.
     *
     * ## OPTIONS
     *
     * [<term_name>]
     * : The term name.
     * 
     * [<from_taxonomy>]
     * : Taxonomy to migrate from.
     * 
     * [<to_taxonomy>]
     * : Taxonomy to migrate to.
     *
     * [--file=<file>]
     * : Path to CSV file for batch merge.
     *
     * [--delete]
     * : Delete the original terms after migrating.
     *
     * [--dry-run]
     * : Simulate actions without making changes.
     *
     * [--log=<logfile>]
     * : Path to a log file for results.
     *
     * ## EXAMPLES
     *
     *     wp boomi taxonomies migrate "Life Sciences" "blog_posts" "industries" 
     *     wp boomi taxonomies migrate "Life Sciences" "blog_posts" "industries" --delete
     *     wp boomi taxonomies merge --file=merge-terms.csv --dry-run --log=merge.log
     *
     * @param string[]             $args       CLI positional arguments.
     * @param array<string, mixed> $assoc_args CLI associative arguments.
     *
     * @return void
     */
    public function migrate_terms( $args, $assoc_args ) {
        // $file_path = $assoc_args['file'] ?? '';
        $dry_run    = isset( $assoc_args['dry-run'] );
        $delete_original = isset( $assoc_args['delete'] );
        $log_name   = $assoc_args['log'] ?? null;

        if ( $log_name ) {
            $this->set_log_name( $log_name );
        }   

        // Batch merge.
        if ( isset( $assoc_args['file'] ) ) {
            if ( is_valid_file( $assoc_args['file'] ) ) {
                // $this->process_csv( $assoc_args['file'], $delete_old, $dry_run );
            }

            $this->display_notices();

            return;
        }

        // Single command.
        $this->process_single_term( $args, $dry_run, $delete_original );

        $this->display_notices();        
    } 

    private function process_csv( string $file, bool $delete_old = false, bool $dry_run = false ) {
        $rows    = array_map( 'str_getcsv', file( $file ) );
        $headers = array_map( 'trim', array_shift( $rows ) );

        if ( ! $this->validate_headers( $headers, array( 'taxonomy', 'from_terms', 'to_term' ) ) ) {
            return;
        }

        foreach ( $rows as $i => $row ) {
            $row_num  = $i + 2;
            $data     = array_combine( $headers, $row );
            $data     = array_map( 'trim', $data );
            $taxonomy = $data['taxonomy'];

            // skip empty lines.
            if ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) {
                continue;
            }

            // Check required fields.
            if ( ! $this->has_required_fields( $data, array( 'taxonomy', 'from_terms', 'to_term' ), $row_num ) ) {
                continue;
            }

            $taxonomy = $this->validate_taxonomy( $taxonomy );

            if ( is_wp_error( $taxonomy ) ) {
                $this->invalid_taxonomy( $taxonomy, $row_num );

                continue;
            }

            $from_terms = explode( '|', $data['from_terms'] );
            $to_term    = $data['to_term'];

            if ( $dry_run ) {
                $this->dry_run_result( $taxonomy, $from_terms, $to_term, $row_num );

                continue;
            }

            $result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $row_num );

            if ( is_wp_error( $result ) ) {
                $this->add_notice( "Row $row_num: Error - " . $result->get_error_message(), 'warning' );
            }
        }

        $this->add_notice( $dry_run ? 'Dry run complete.' : 'Batch merge complete.', 'success' );
    }

    private function process_single_term( array $args, $dry_run, $delete_original ) {
        if ( ! $this->validate_command_args( $args, 3, 3 ) ) {
            $this->add_notice( 'Invalid arguments. Usage: wp taxonomy migrate <term_name> <from_taxonomy> <to_taxonomy>', 'error' );
            $this->log( 'Invalid arguments. Usage: wp taxonomy migrate <term_name> <from_taxonomy> <to_taxonomy>', 'error' );

            return;
        }

        list( $term_name, $from_tax, $to_tax ) = $args;      
 
        $result = $this->migrate( $term_name, $from_tax, $to_tax );

        if ( is_wp_error( $result ) ) {
            $this->add_notice( $result->get_error_message(), 'error' );
        } else {
            $this->add_notice( "Migrated '$term_name' from '$from_tax' to '$to_tax'", 'success' );
        }

        $this->display_notices();
    }

    protected function migrate( string $term_name, string $from_tax, string $to_tax, $row_num = null ) {
        $source_term = get_term_by( 'name', $term_name, $from_tax );

        if ( ! $source_term ) {
            $this->add_notice( "Term '$term_name' not found in taxonomy '$from_tax'.", 'error' );
            $this->log( "Term '$term_name' not found in taxonomy '$from_tax'.", 'error' );
            
            return new WP_Error( 'term_not_found', "Term '$term_name' not found in taxonomy '$from_tax'." );
        }

        // Ensure destination term exists or create it.
        $dest_term = get_term_by( 'name', $term_name, $to_tax );

        if ( ! $dest_term ) {
            $dest_term = wp_insert_term( $term_name, $to_tax );
        
            if ( is_wp_error( $dest_term ) ) {
                $this->add_notice( "Failed to create term '$term_name' in taxonomy '$to_tax': " . $dest_term->get_error_message(), 'error' );
                $this->log( "Failed to create term '$term_name' in taxonomy '$to_tax': " . $dest_term->get_error_message(), 'error' );

                return $dest_term;
            }
        
            $dest_term_id = $dest_term['term_id'];
        
            $this->add_notice( "Created new term '$term_name' in taxonomy '$to_tax'.", 'success' );
            $this->log( "Created new term '$term_name' in taxonomy '$to_tax'.", 'success' );
        } else {
            $dest_term_id = $dest_term->term_id;
        }

        // Get posts with the source term.
        $posts = get_posts( [
            'post_type' => 'any',
            'tax_query' => [
                [
                    'taxonomy' => $from_tax,
                    'field'    => 'term_id',
                    'terms'    => $source_term->term_id,
                ]
            ],
            'posts_per_page' => -1,
            'fields' => 'ids',
        ] ); 
        
        if (empty($posts)) {
            $this->add_notice( "No posts found with term '$term_name' in taxonomy '$from_tax'.", 'warning' );
            $this->log( "No posts found with term '$term_name' in taxonomy '$from_tax'.", 'warning' );
            
            return new WP_Error( 'no_posts_found', "No posts found with term '$term_name' in taxonomy '$from_tax'." );
        }

        // Assign destination term to each post.
        foreach ( $posts as $post_id ) {
            wp_set_object_terms( $post_id, $dest_term_id, $to_tax, true );
        }

        $this->add_notice( count( $posts ) . " post(s) updated with '$term_name' in taxonomy '$to_tax'.", 'success' );
        $this->log( count( $posts ) . " post(s) updated with '$term_name' in taxonomy '$to_tax'.", 'success' );

        if ( $delete_original ) {
            $result = wp_delete_term( $source_term->term_id, $from_tax );

            if ( is_wp_error( $result ) ) {
                $this->add_notice( "Failed to delete term '$term_name' from taxonomy '$from_tax': " . $result->get_error_message(), 'error' );
                $this->log( "Failed to delete term '$term_name' from taxonomy '$from_tax': " . $result->get_error_message(), 'error' );

                return $result;
            } else {
                $this->add_notice( "Deleted term '$term_name' from taxonomy '$from_tax'.", 'success' );
                $this->log( "Deleted term '$term_name' from taxonomy '$from_tax'.", 'success' );
            }
        }

        return true;
    }
}

/*

class Bulk_Term_Migrator {
    private $dry_run = false;
    private $logger = null;

    public function __invoke( $args, $assoc_args ) {
        $file_path = $assoc_args['file'] ?? '';
        $this->dry_run = isset( $assoc_args['dry-run'] );
        $delete_original = isset( $assoc_args['delete'] );
        $log_file = $assoc_args['log'] ?? null;

        if ( ! file_exists( $file_path ) ) {
            WP_CLI::error( 'CSV file not found: ' . $file_path );
        }

        if ( $log_file ) {
            $this->logger = fopen( $log_file, 'a' );
            if ( ! $this->logger ) {
                WP_CLI::error( 'Failed to open log file: ' . $log_file );
            }
            $this->log( "Starting bulk migration. Dry-run: " . ($this->dry_run ? 'yes' : 'no') );
        }

        $file = fopen( $file_path, 'r' );
        if ( ! $file ) {
            WP_CLI::error( 'Could not open CSV file.' );
        }

        $line_number = 0;

        while ( ( $line = fgetcsv( $file ) ) !== false ) {
            $line_number++;

            if ( $line_number === 1 && strtolower( $line[0] ) === 'term_name' ) {
                continue;
            }

            if ( count( $line ) !== 3 ) {
                $this->warn( "Line $line_number: Invalid format." );
                continue;
            }

            list( $term_name, $from_tax, $to_tax ) = array_map( 'trim', $line );
            $this->log( "Line $line_number: Processing term '$term_name' from '$from_tax' to '$to_tax'" );

            $source_term = get_term_by( 'name', $term_name, $from_tax );
            if ( ! $source_term ) {
                $this->warn( "Line $line_number: Term '$term_name' not found in '$from_tax'." );
                continue;
            }

            $dest_term = get_term_by( 'name', $term_name, $to_tax );
            if ( ! $dest_term && ! $this->dry_run ) {
                $dest_term = wp_insert_term( $term_name, $to_tax );
                if ( is_wp_error( $dest_term ) ) {
                    $this->warn( "Line $line_number: Failed to create '$term_name' in '$to_tax' — " . $dest_term->get_error_message() );
                    continue;
                }
                $dest_term_id = $dest_term['term_id'];
                $this->log( "Line $line_number: Created term '$term_name' in '$to_tax'." );
            } else {
                $dest_term_id = is_object( $dest_term ) ? $dest_term->term_id : ($dest_term['term_id'] ?? 0);
            }

            $posts = get_posts( [
                'post_type' => 'any',
                'tax_query' => [
                    [
                        'taxonomy' => $from_tax,
                        'field'    => 'term_id',
                        'terms'    => $source_term->term_id,
                    ]
                ],
                'posts_per_page' => -1,
                'fields' => 'ids',
            ] );

            if ( empty( $posts ) ) {
                $this->log( "Line $line_number: No posts found for '$term_name' in '$from_tax'." );
                continue;
            }

            foreach ( $posts as $post_id ) {
                if ( ! $this->dry_run ) {
                    wp_set_object_terms( $post_id, $dest_term_id, $to_tax, true );
                }
            }

            $this->success( "Line $line_number: " . count( $posts ) . " post(s) would be migrated." );

            if ( $delete_original ) {
                if ( $this->dry_run ) {
                    $this->log( "Line $line_number: Would delete term '$term_name' from '$from_tax'." );
                } else {
                    $result = wp_delete_term( $source_term->term_id, $from_tax );
                    if ( is_wp_error( $result ) ) {
                        $this->warn( "Line $line_number: Failed to delete term — " . $result->get_error_message() );
                    } else {
                        $this->log( "Line $line_number: Deleted term '$term_name' from '$from_tax'." );
                    }
                }
            }
        }

        fclose( $file );
        if ( $this->logger ) {
            fclose( $this->logger );
        }

        WP_CLI::success( $this->dry_run ? 'Dry-run complete.' : 'Bulk migration complete.' );
    }


}
    */