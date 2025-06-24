<?php
/**
 * Term Sync CLI commands class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.3.3
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use WP_CLI;

/**
 * Migrate CLI class.
 */
class TermSyncCommands extends CLICommands {

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
		$parent = 'boomi taxonomies term-sync';

		// Define mapping of commands to their respective classes.
		$commands = array(
			'match' => array(
				'class'     => __NAMESPACE__ . '\TermSync',
				'shortdesc' => 'Taxonomy term syncing',
				'longdesc'  => 'Taxonomy term syncing',
				'method'    => 'match',
			),
			'bulk'  => array(
				'class'     => __NAMESPACE__ . '\TermSync',
				'shortdesc' => 'Bulk taxonomy term syncing',
				'longdesc'  => 'Bulk taxonomy term syncing',
				'method'    => 'bulk',
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
