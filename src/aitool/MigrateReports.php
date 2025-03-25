<?php
/**
 * Migrate Reports Data class
 *
 * @package erikdmitchell\bcmigration\aitool
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\aitool;

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
    private $db;

    /**
     * The path to the upload directory.
     *
     * @var string
     */
    private $upload_dir_path;

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
     * @access private
     */
    private function __construct() {
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
     * Migrates AI Tool data.
     *
     * Finds all the files in the bc-ai-tool-data directory and migrates them to the database.
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
     * Get all the files in the bc-ai-tool-data directory.
     *
     * Finds all the files in the bc-ai-tool-data directory and returns them as an array.
     * If the directory does not exist, or if there are no files with the name 'data-*', an empty array is returned.
     *
     * @return array
     */
    private function get_files() {
        $data_files = array();
        $path       = $this->upload_dir_path . '/bc-ai-tool-data';

        if ( ! is_dir( $path ) ) {
            return array();
        }

        foreach ( glob( $path . '/*.json' ) as $file ) {
            if ( strpos( basename( $file ), 'data-' ) !== false ) {
                $data_files[] = $file;
            }
        }

        if ( empty( $data_files ) ) {
            return array();
        }

        $data_files = $this->clean_files( $data_files );

        return $data_files;
    }

    /**
     * Migrates the AI Tool reports from the JSON files to the database.
     *
     * @param array $files Array of JSON files to be migrated.
     *
     * @return array Array of IDs of migrated reports.
     */
    private function migrate_reports( array $files ) {
        $migrated_reports = array();

        if ( empty( $files ) ) {
            return false;
        }

        foreach ( $files as $file ) {
            $prepared_data = $this->prepare_report_for_db( $file, 'aitool' );

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
     * Prepares the given report item for insertion into the database.
     *
     * The method will read the given file, convert the keys from camel case to snake case,
     * prepare the item for the database by setting the created date to the current time if not provided,
     * and then add the app name to the data and set the report_url to the boomi_link.
     * If the job key exists, it is removed after being used to set the title.
     * The boomi_link key is removed after being used to set the report_url.
     *
     * @param string $file The json file to read.
     * @param string $app  The app name.
     *
     * @return array The prepared report item.
     */
    private function prepare_report_for_db( string $file, string $app ) {
        $data               = (array) json_decode( file_get_contents( $file ) );
        $data               = $this->maybe_format_keys( $data );
        $data               = $this->prepare_item_for_db( $data );
        $data['title']      = isset( $data['job'] ) ? $data['job'] : null;
        $data['report_url'] = $data['boomi_link'];
        $data['app']        = $app;

        if ( isset( $data['job'] ) ) {
            unset( $data['job'] );
        }

        unset( $data['boomi_link'] );

        return $data;
    }

    /**
     * Converts the keys of the given array from camel case to snake case.
     *
     * @param array $data The array with camel case keys.
     * @return array The array with keys converted to snake case.
     */
    private function maybe_format_keys( array $data ) {
        return array_combine(
            array_map( 'erikdmitchell\\bcmigration\\camel_to_snake', array_keys( $data ) ),
            array_values( $data )
        );
    }

    /**
     * Prepares the given report item for insertion into the database.
     *
     * Sets the created date to the current time if not provided.
     * Converts the integrationCampaign2 key to report_url.
     * Serializes the data if it isn't already.
     *
     * @param array $item The report item to prepare.
     *
     * @return array The prepared report item.
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
     * Checks if a db entry exists, given the first name, last name, and email.
     *
     * If the data is not valid (e.g. empty or not set), this function will return true
     * to indicate that the data should be skipped.
     *
     * @param array $data The data to check for.
     *
     * @return bool True if the database entry exists, false otherwise.
     */
    private function db_entry_exists( array $data ) {
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
    private function insert_into_db( array $data ) {
        $db_id = $this->db->add( $data );

        if ( ! $db_id ) {
            return new WP_Error(
                'aitool_reports_insert_into_db',
                'Failed to insert data'
            );
        }

        return $db_id;
    }

    /**
     * Clean up the given files.
     *
     * @param array $files The files to clean up.
     *
     * @return array The cleaned up files.
     */
    private function clean_files( array $files ) {
        foreach ( $files as $key => $file ) {
            // Decode the JSON file.
            $data = json_decode( file_get_contents( $file ), true );

            if ( ! isset( $data['FirstName'] ) || ! isset( $data['LastName'] ) || ! isset( $data['Email'] ) ) {
                unset( $files[ $key ] );
                continue;
            }

            if ( ! isset( $data['BoomiLink'] ) || empty( $data['BoomiLink'] ) ) {
                $data['BoomiLink'] = 'https://boomi.com/platform/ai/aira/#/report';
            }

            // Encode the modified data back to JSON and save it to the file.
            file_put_contents( $file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );
        }

        $files = array_values( $files );

        return $files;
    }
}
