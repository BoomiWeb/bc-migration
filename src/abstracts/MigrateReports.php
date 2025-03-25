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
     * @var bool
     */
    protected static $instance = false;


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
        $this->db      = \BoomiCMS\BC_DB::getInstance()->report_data();
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

    protected function get_files() {    
        return array();
    }

    protected function migrate_reports( array $files ) {
        return array();
    }

    /**
     * Converts the keys of the given associative array from camel case to snake case.
     *
     * Utilizes the `bc_camel_to_snake` function to transform each key in the array.
     *
     * @param array $data The associative array with camel case keys to be transformed.
     * @return array The array with keys converted to snake case.
     */
    protected function maybe_format_keys( array $data ) {
        return array_combine(
            array_map( 'bc_camel_to_snake', array_keys( $data ) ),
            array_values( $data )
        );
    }

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

    protected function insert_into_db( array $data ) { 
        $db_id = $this->db->add( $data );

        if ( ! $db_id ) {
            return new WP_Error(
                'aitool_reports_insert_into_db',
                'Failed to insert data',
            );
        }

        return $db_id;
    }

}