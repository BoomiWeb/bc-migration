<?php
/**
 * Migrate Reports Data class
 *
 * @package erikdmitchell\bcmigration\abstracts
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\abstracts;

use WP_Error;

/**
 * MigrateReports class.
 */
class MigrateReports {

    /**
     * The database instance for report data.
     *
     * @var object
     */
    protected $db;

    /**
     * The path to the upload directory.
     *
     * @var string
     */
    protected $upload_dir_path;

    /**
     * The single instance of the class.
     *
     * @var MigrateReports|null
     */
    protected static ?MigrateReports $instance = null;

    /**
     * Constructor.
     *
     * Initializes the MigrateReports class by setting up the upload directory path
     * and database instance for report data.
     *
     * @access protected
     */
    protected function __construct() {
        $upload_dir = wp_upload_dir();

        $this->upload_dir_path = $upload_dir['basedir'];
        $this->db              = \BoomiCMS\BC_DB::getInstance()->report_data();
    }

    /**
     * Gets the single instance of the class.
     *
     * @return MigrateReports Single instance of the class.
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Migrates data.
     *
     * Finds all the files in the bc-apiida directory and migrates them to the database.
     *
     * @return array An array of migrated data.
     */
    public function migrate_data() {
        $files = $this->get_files();

        if ( empty( $files ) ) {
            return array();
        }

        return $this->migrate_reports( $files );
    }

    /**
     * Gets all the files in the bc-apiida directory.
     *
     * Finds all the files in the bc-apiida directory and returns them as an array.
     * If the directory does not exist, or if there are no files with the name 'data-*', an empty array is returned.
     *
     * @return array
     */
    protected function get_files() {
        return array();
    }

    /**
     * Migrates the AI Tool reports from the JSON files to the database.
     *
     * @param array $files Array of JSON files to be migrated.
     *
     * @return array Array of IDs of migrated reports.
     */
    protected function migrate_reports( array $files ) {
        return $files;
    }

    /**
     * Converts the keys of the given associative array from camel case to snake case.
     *
     * Utilizes the `camel_to_snake` function to transform each key in the array.
     *
     * @param array $data The associative array with camel case keys to be transformed.
     * @return array The array with keys converted to snake case.
     */
    protected function maybe_format_keys( array $data ) {
        return array_combine(
            array_map( 'erikdmitchell\\bcmigration\\camel_to_snake', array_keys( $data ) ),
            array_values( $data )
        );
    }

    /**
     * Checks if a db entry exists, given the first name, last name, and email.
     *
     * If the data is not valid (e.g. empty or not set), this function will return true
     * to indicate that the data should be skipped.
     *
     * @param array $data The data to check for.
     *
     * @return bool True if the database entry exists, false otherwise.
     */
    protected function db_entry_exists( array $data ) {
        if ( ! isset( $data['first_name'] ) || ! isset( $data['last_name'] ) || ! isset( $data['email'] ) ) {
            return true; // the data is not valid, so we need to skip it anyway.
        }

        if ( empty( $data['first_name'] ) || empty( $data['last_name'] ) || empty( $data['email'] ) ) {
            return true; // the data is not valid, so we need to skip it anyway.
        }

        $exits = $this->db->get_column_by_where(
            'id',
            array(
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
            )
        );

        if ( ! empty( $exits ) ) {
            return true;
        }

        return false;
    }

    /**
     * Inserts the given data into the database.
     *
     * Attempts to insert the provided array of data into the database.
     * If the insertion fails, a WP_Error object is returned indicating
     * the failure. If successful, the ID of the inserted row is returned.
     *
     * @param array $data The data to insert.
     *
     * @return int|WP_Error The ID of the inserted row, or a WP_Error object on failure.
     */
    protected function insert_into_db( array $data ) {
        $db_id = $this->db->add( $data );

        if ( ! $db_id ) {
            return new WP_Error(
                'apiida_reports_insert_into_db',
                'Failed to insert data'
            );
        }

        return $db_id;
    }
}
