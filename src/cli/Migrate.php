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
        $parent = 'boomi migrate';
    
        // Define mapping of commands to their respective classes
        $commands = array(
            'subprocessors' => array(
                'class'     => __NAMESPACE__ . '\Subprocessors',
                'shortdesc' => 'Migrate subprocessors data',
                'longdesc'  => 'Migrate subprocessors data',
                'method'    => 'migrate',
            ),
            'aitool' => array(
                'class'     => __NAMESPACE__ . '\AITool',
                'shortdesc' => 'Migrate AI Tool data',
                'longdesc'  => 'Migrate AI Tool data',
                'method'    => 'migrate',
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
