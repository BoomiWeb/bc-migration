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
use WP_CLI;

/**
 * Validate a file path for CLI commands.
 *
 * @param string $file Path to a file.
 *
 * @return bool True if the file is valid, false otherwise.
 */
function is_valid_file( string $file = '' ) {
    if ( ! file_exists( $file ) ) {
        WP_CLI::error( "File not found: $file" );
    }

    if ( ! is_readable( $file ) ) {
        WP_CLI::error( "File is not readable: $file" );
    }

    if ( pathinfo( $file, PATHINFO_EXTENSION ) !== 'csv' ) {
        WP_CLI::error( "File is not a CSV: $file" );
    }

    return true;
}
