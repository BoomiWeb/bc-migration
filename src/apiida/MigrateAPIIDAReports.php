<?php
/**
 * Migrate APIIDA Reports Data class
 *
 * @package erikdmitchell\bcmigration\apiida
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\apiida;

use erikdmitchell\bcmigration\abstracts\MigrateReports;
use WP_Error;

/**
 * MigrateAPIIDAReports class.
 */
class MigrateAPIIDAReports extends MigrateReports {

	/**
	 * Gets the single instance of the class.
	 *
	 * @return MigrateAPIIDAReports Single instance of the class.
	 */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Gets all the files in the bc-apiida/results directory.
	 *
	 * Finds all the files in the bc-apiida/results directory and returns them as an array.
	 * If the directory does not exist, or if there are no files with the name 'data-*', an empty array is returned.
	 *
	 * @return array<string> Array of file paths.
	 */
	protected function get_files() {
		$data_files = array();
		$path       = $this->upload_dir_path . '/bc-apiida/results';

		if ( ! is_dir( $path ) ) {
			return array();
		}

		foreach ( glob( $path . '/*.json' ) as $file ) {
			$data_files[] = $file;
		}

		if ( empty( $data_files ) ) {
			return array();
		}

		$data_files = $this->clean_files( $data_files );

		return $data_files;
	}

	/**
	 * Migrates the APIIDA reports from the provided JSON files to the database.
	 *
	 * Iterates over each file, prepares the data for database insertion, and inserts it if
	 * it does not already exist in the database. If an error occurs during insertion, the
	 * file is skipped. Only successfully inserted report IDs are returned.
	 *
	 * @param array<int, string> $files Array of JSON files to be migrated.
	 * @return array<int, string>|list<int>|false Array of IDs of migrated reports, or false if no files are provided.
	 */
	protected function migrate_reports( array $files ) {
		$migrated_reports = array();

		if ( empty( $files ) ) {
			return false;
		}

		foreach ( $files as $file ) {
			$prepared_data = $this->prepare_report_for_db( $file, 'apiida' ); // TODO: same as MigrateReports - this is the only diff.

			if ( empty( $prepared_data ) ) {
				continue;
			}

			if ( $this->db_entry_exists( $prepared_data ) ) {
				continue;
			}

			$db_id = $this->insert_into_db( $prepared_data );

			if ( is_wp_error( $db_id ) ) {
				continue;
			}

			$migrated_reports[] = $db_id;
		}

		return $migrated_reports;
	}

	/**
	 * Prepares a report item from a JSON file for database insertion.
	 *
	 * This method reads a JSON file, converts its contents to an array, formats the keys,
	 * and prepares the data for insertion into the database. It sets the report title using
	 * the 'job' key if available, assigns the application name to the 'app' key, and removes
	 * the 'job' key after use.
	 *
	 * @param string $file The path to the JSON file to read.
	 * @param string $app  The application name to associate with the report.
	 * @return array<string> The prepared report data ready for database insertion.
	 */
	private function prepare_report_for_db( string $file, string $app ) {
		$data = (array) json_decode( file_get_contents( $file ) );
		$data = $this->maybe_format_keys( $data );
		$data = $this->prepare_item_for_db( $data );

		$data['title'] = isset( $data['job'] ) ? $data['job'] : null;
		$data['app']   = $app;

		if ( isset( $data['job'] ) ) {
			unset( $data['job'] );
		}

		return $data;
	}

	/**
	 * Prepares a report item from a JSON file for database insertion.
	 *
	 * Sets the created date to the current time if not provided.
	 * Converts the integrationCampaign2 key to report_url.
	 * Serializes the data if it isn't already.
	 *
	 * @param array<string, mixed> $item The report item to prepare.
	 * @return array<string, mixed> The prepared report item.
	 */
	private function prepare_item_for_db( array $item ) {
		if ( ! isset( $item['created'] ) || empty( $item['created'] ) ) {
			$item['created'] = current_time( 'mysql' );
		}

		if ( isset( $item['integrationCampaign2'] ) ) {
			$item['report_url'] = $item['integrationCampaign2'];

			unset( $item['integrationCampaign2'] );
		}

		$item['data'] = maybe_serialize( $item['data'] );

		return $item;
	}

	/**
	 * Cleans up the given files.
	 *
	 * Iterates over the given array of files and removes any files that do not contain
	 * the required keys 'FirstName', 'LastName', and 'Email'. It then adds a report_url
	 * to the data in the file by prepending the site URL and '#report-' to the filename.
	 * The modified data is then encoded back to JSON and saved to the file.
	 *
	 * @param array<string> $files The files to clean up.
	 * @return array<string> The cleaned up files.
	 */
	protected function clean_files( array $files ) {
		foreach ( $files as $key => $file ) {
			// Decode the JSON file.
			$data = json_decode( file_get_contents( $file ), true );

			// Modified condition to check for either 'FirstName' or 'firstName', etc.
			if (
				( ! isset( $data['FirstName'] ) && ! isset( $data['firstName'] ) ) ||
				( ! isset( $data['LastName'] ) && ! isset( $data['lastName'] ) ) ||
				( ! isset( $data['Email'] ) && ! isset( $data['email'] ) )
			) {
				unset( $files[ $key ] );
				continue;
			}

			$filename   = pathinfo( $file, PATHINFO_FILENAME );
			$report_url = site_url() . '/api-management-maturity-assessment/#report-' . $filename;

			$data['report_url'] = $report_url;

			// Encode the modified data back to JSON and save it to the file.
			file_put_contents( $file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );
		}

		$files = array_values( $files );

		return $files;
	}
}
