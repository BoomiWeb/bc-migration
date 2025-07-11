<?php
/**
 * Rivery CLI commands class
 *
 * @package erikdmitchell\bcmigration\cli\rivery
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\rivery;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use WP_CLI;

/**
 * Rivery CLI commands class.
 */
class Commands extends CLICommands {

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
		$parent = 'boomi rivery';

		// Define mapping of commands to their respective classes.
		$commands = array(
			'migrate-integrations' => array(
				'class'     => __NAMESPACE__ . '\MigrateIntegrations',
				'shortdesc' => 'Migrate Rivery integrations',
				'longdesc'  => 'Migrate Rivery integrations',
				'method'    => 'run',
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
