<?php
/**
 * Taxonomies CLI functions
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\cli\CLIHelper;

function is_valid_file(string $file = '') {
    if ( ! file_exists( $file ) ) {
        CLIHelper::output( "CSV file not found: $file", 'error' );

        return false;
    }

    if ( ! is_readable( $file ) ) {
        CLIHelper::output( "CSV file not readable: $file", 'error' );

        return false;
    }

    if ( pathinfo( $file, PATHINFO_EXTENSION ) !== 'csv' ) {
        CLIHelper::output( "File is not a CSV: $file", 'error' );

        return false;
    }

    return true;
}
