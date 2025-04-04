<?php
/**
 * Taxonomies CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use WP_CLI;

/**
 * Taxonomies CLI class.
 */
class Taxonomies extends CLICommands {

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
        $parent = 'boomi taxonomies';

        // Define mapping of commands to their respective classes.
        $commands = array(
            'rename' => array(
                'class'     => __NAMESPACE__ . '\Rename',
                'shortdesc' => 'Rename Taxonomies',
                'longdesc'  => 'Rename Taxonomies',
                'method'    => 'rename_term',
            ),         
        );

        foreach ( $commands as $command => $config ) {
            WP_CLI::add_command(
                "{$parent} {$command}",
                array( $config['class'], $config['method'] ),
                array(
                    'shortdesc' => $config['shortdesc'],
                    'longdesc'  => $config['longdesc'],
                    'synopsis'  => array(),
                )
            );
        }
    }
}