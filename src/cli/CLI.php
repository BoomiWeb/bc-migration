<?php
/**
 * Enables CLI commands
 *
 * @package erikdmitchell\bcmigration\cli
 * @since 0.1.0
 * @version 0.2.0
 */

namespace erikdmitchell\bcmigration\cli;

use WP_CLI;

/**
 * CLI class.
 */
class CLI {
	/**
	 * Load required files and hooks to make the CLI work.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Sets up and hooks WP CLI to our CLI code.
	 *
	 * @return void
	 */
	private function hooks() {
		WP_CLI::add_hook( 'after_wp_load', __NAMESPACE__ . '\Migrate::register_commands' );
		WP_CLI::add_hook( 'after_wp_load', __NAMESPACE__ . '\taxonomies\Taxonomies::register_commands' );
		WP_CLI::add_hook( 'after_wp_load', __NAMESPACE__ . '\taxonomies\TermSyncCommands::register_commands' );
	}
}
