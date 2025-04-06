<?php
/**
 * Process CSV trait class
 *
 * @package erikdmitchell\bcmigration\traits
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\traits;

use erikdmitchell\bcmigration\cli\CLI;
use erikdmitchell\bcmigration\cli\CLIHelper;

trait ProcessCSVTrait {
    
    private function process_csv( $file, $delete_old = false, $dry_run = false, $log = null ) {
        $rows = array_map( 'str_getcsv', file( $file ) );
        $header = array_map( 'trim', array_shift( $rows ) );
        $required = [ 'taxonomy', 'from_terms', 'to_term' ];

        $missing = array_diff( $required, $header );

        if ( ! empty( $missing ) ) {
            CLIHelper::output( 'CSV is missing required columns: ' . implode( ', ', $missing ), 'error' );
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

                CLIHelper::output( $message );

                continue;
            }

            $result = $this->merge( $taxonomy, $from_terms, $to_term, $delete_old, $log, $row_num, $post_type );

            if ( is_wp_error( $result ) ) {
                CLIHelper::output( "Row $row_num: Error - " . $result->get_error_message(), 'warning' );
            }
        }

        CLIHelper::output( $dry_run ? 'Dry run complete.' : 'Batch merge complete.', 'success' );

        return;        
    }

}