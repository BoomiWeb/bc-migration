<?php
/**
 * APIIDA CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use erikdmitchell\bcmigration\apiida\MigrateAPIIDAReports;
use WP_CLI;

/**
 * APIIDA CLI class.
 */
class APIIDA extends CLICommands {

	/**
	 * Migrates APIIDA data.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : The action to perform. Currently only `reports` and `remove-folder` are supported.
	 *
	 * ## EXAMPLES
	 *
	 *     # Migrate all APIIDA data.
	 *     $ wp boomi migrate apiida all
	 *     Success: Migrated 13 reports.
	 *     Success: Removed the APIIDA uploads folder.
	 *
	 *     # Migrate APIIDA reports.
	 *     $ wp boomi migrate apiida reports
	 *     Success: Migrated 13 reports.
	 *
	 *     # Remove the APIIDA uploads folder.
	 *     $ wp boomi migrate apiida remove-folder
	 *     Success: Removed the APIIDA uploads folder.
	 *
	 * @param string[]             $args       CLI positional arguments.
	 * @param array<string, mixed> $assoc_args CLI associative arguments.
	 * @return void
	 */
	public function migrate( $args, $assoc_args ) {
		list ( $action ) = $args;

		if ( empty( $action ) ) {
			WP_CLI::error( 'Invalid arguments. Requires action and post_id' );
		}

		switch ( $action ) {
			case 'all':
				$this->migrate_reports();
				$this->remove_folder();
				break;
			case 'reports':
				$this->migrate_reports();
				break;
			case 'remove-folder':
				$this->remove_folder();
				break;
			default:
				WP_CLI::error( 'Invalid action.' );
				break;
		}
	}

	/**
	 * Migrates APIIDA reports to the database.
	 *
	 * This method will log messages to the user about the migration process and
	 * report the number of migrated records.
	 * 
	 * @return void
	 */
	private function migrate_reports() {
		WP_CLI::log( 'Migrating APIIDA reports...' );

		$migrated_data = MigrateAPIIDAReports::init()->migrate_data();

		if ( empty( $migrated_data ) ) {
			WP_CLI::log( 'No data to migrate.' );

			return;
		}

		WP_CLI::success( count( $migrated_data ) . ' APIIDA reports migrated successfully.' );
	}

	/**
	 * Removes the APIIDA uploads folder.
	 *
	 * This method will log messages to the user about the deletion process.
	 * 
	 * @return void
	 */
	private function remove_folder() {
		$upload_dir = wp_upload_dir( null, false );
		$deleted    = $this->remove_directory( $upload_dir['basedir'] . '/bc-apiida' );

		if ( $deleted ) {
			WP_CLI::success( 'APIIDA uploads folder deleted.' );
		} else {
			WP_CLI::error( 'APIIDA uploads folder deletion failed.' );
		}
	}

	/**
	 * Removes a directory recursively.
	 *
	 * @param string $dir The path to the directory to be deleted.
	 * @return bool True if the directory was deleted successfully, false otherwise.
	 */
	private function remove_directory( string $dir ) {
		if ( ! file_exists( $dir ) ) {
			return true;
		}

		if ( ! is_dir( $dir ) ) {
			return wp_delete_file( $dir );
		}

		foreach ( scandir( $dir ) as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			if ( ! $this->remove_directory( $dir . DIRECTORY_SEPARATOR . $item ) ) {
				return false;
			}
		}

		return rmdir( $dir );
	}
}
