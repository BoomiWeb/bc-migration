<?php
/**
 * Taxonomies CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
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
     * Register the commands.
     *
     * @return void
     */
    public static function register_commands() {
        include_once __DIR__ . '/functions.php';

        $parent = 'boomi taxonomies';

        // Define mapping of commands to their respective classes.
        $commands = array(
            'rename'         => array(
                'class'     => __NAMESPACE__ . '\Rename',
                'shortdesc' => 'Rename Taxonomies (terms)',
                'longdesc'  => 'Rename Taxonomies (terms)',
                'method'    => 'rename_term',
            ),
            'merge'          => array(
                'class'     => __NAMESPACE__ . '\Merge',
                'shortdesc' => 'Merge Taxonomies (terms)',
                'longdesc'  => 'Merge Taxonomies (terms)',
                'method'    => 'merge_terms',
            ),
            'delete'         => array(
                'class'     => __NAMESPACE__ . '\Delete',
                'shortdesc' => 'Delete Taxonomies (terms)',
                'longdesc'  => 'Delete Taxonomies (terms)',
                'method'    => 'delete_terms',
            ),
            'term-validator' => array(
                'class'     => __NAMESPACE__ . '\TermValidator',
                'shortdesc' => 'Validate Taxonomies (terms)',
                'longdesc'  => 'Validate Taxonomies (terms)',
                'method'    => 'validate_terms',
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
