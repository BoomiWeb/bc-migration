<?php
/**
 * Subprocessors CLI class
 *
 * @package erikdmitchell\bcmigration\WP_CLI
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

use WP_CLI;

/**
 * Subprocessors CLI class.
 */
class Subprocessors extends CLICommands {

    /**
     * Construct
     */
    public function __construct() {}

    /**
     * Register the commands.
     *
     * @return void
     */
    public static function register_commands() {
        $parent             = 'bc migrate subprocessors';
        $supported_commands = array( 'subscribe-data' );

        foreach ( $supported_commands as $command ) {
            $synopsis = array();

            if ( 'subscribe-data' === $command ) {
                $shortdesc = 'Migrate subprocessors subscribe data';
                $longdesc  = 'Migrate subprocessors subscribe data';
                $method    = 'migrate';
            }

            WP_CLI::add_command(
                "{$parent} {$command}",
                array( 'Subprocessors', $method ),
                array(
                    'shortdesc' => $shortdesc,
                    'longdesc'  => $longdesc,
                    'synopsis'  => $synopsis,
                )
            );
        }
    }

    /**
     * Migrate subprocessors subscribe data.
     */
    public function import( $args, $assoc_args ) {
        WP_CLI::log( 'Migrating subprocessors subscribe data...' );

        $message = '';
        // $import  = BC_Connectors_Update::instance();
        // $message = $import->run_import_process();

        // output message if it exists.
        // if ( '' !== $message ) {
            // WP_CLI::log( $message );
        // }

        WP_CLI::log( 'Migration complete.' );
    }
}
