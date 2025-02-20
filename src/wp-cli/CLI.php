<?php
/**
 * Enables CLI commands
 *
 * @package erikdmitchell\bcmigration\WP_CLI
 * @since 0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

use WP_CLI;

/**
 * CLI class.
 */
class CLI {
    /**
     * Load required files and hooks to make the CLI work.
     */
    public function __construct() {
        $this->includes();
        $this->hooks();
    }

    /**
     * Load command files.
     *
     * @return void
     */
    private function includes() {
        // require_once __DIR__ . '/Subprocessors.php';
    }

    /**
     * Sets up and hooks WP CLI to our CLI code.
     *
     * @return void
     */
    private function hooks() {
        WP_CLI::add_hook( 'after_wp_load', 'Subprocessors::register_commands' );
    }
}
