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
            'migrate'          => array(
                'class'     => __NAMESPACE__ . '\Migrate',
                'shortdesc' => 'Migrate terms between different Taxonomies.',
                'longdesc'  => 'Migrate terms between different Taxonomies.',
                'method'    => 'migrate_terms',
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
            'update_terms'   => array(
                'class'     => __NAMESPACE__ . '\UpdateTerms',
                'shortdesc' => 'Update Taxonomies (terms)',
                'longdesc'  => 'Update Taxonomies (terms)',
                'method'    => 'update_terms',
            ),
        );

        foreach ( $commands as $command => $config ) {    
            WP_CLI::add_command(
                "{$parent} {$command}",
                array( $config['class'], $config['method'] ),
                array(
                    'shortdesc' => $config['shortdesc'],
                    'longdesc'  => $config['longdesc'],
                )
            );
        }
    }
}
