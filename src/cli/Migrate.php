<?php
/**
 * Migrate CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use WP_CLI;

/**
 * Migrate CLI class.
 */
class Migrate extends CLICommands {

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
        $parent             = 'boomi migrate';
        $supported_commands = array( 'subprocessors' );

        foreach ( $supported_commands as $command ) {
            $synopsis = array();

            if ( 'subprocessors' === $command ) {
                $shortdesc = 'Migrate subprocessors data';
                $longdesc  = 'Migrate subprocessors data';
                $method    = 'migrate';
            }

            WP_CLI::add_command(
                "{$parent} {$command}",
                array( __NAMESPACE__ . '\Subprocessors', $method ),
                array(
                    'shortdesc' => $shortdesc,
                    'longdesc'  => $longdesc,
                    'synopsis'  => $synopsis,
                )
            );
        }
    }

}
