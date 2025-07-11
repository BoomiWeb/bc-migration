<?php
/**
 * Rivery migrate integrations CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\rivery;

use erikdmitchell\bcmigration\rivery\import\Integrations;
use erikdmitchell\bcmigration\rivery\Rivery;
use WP_CLI;

/**
 * MigrateIntegrations CLI class.
 */
class MigrateIntegrations {

	/**
	 * Migrates Rivery integrations data.
	 *
	 * @param string[]             $args       CLI positional arguments.
	 * @param array<string, mixed> $assoc_args CLI associative arguments.
	 * @return void
	 */
	public function run( $args, $assoc_args ) {
        WP_CLI::log( 'Starting Rivery integrations migration...' );
        WP_CLI::log('This should run a bg process to migrate Rivery integrations.');

        $integrations = Integrations::init()->get_integrations();

        if ( is_wp_error( $integrations ) ) {
            WP_CLI::error( 'Failed to fetch Rivery integrations: ' . $integrations->get_error_message() );
            return;
        }

        if ( empty( $integrations ) ) {
            WP_CLI::success( 'No integrations found to migrate.' );
            return;
        }

        $formatted_integrations = Integrations::init()->format_integrations( $integrations );

        print_r($formatted_integrations);
    }

}