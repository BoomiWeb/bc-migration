<?php
/**
 * Migrate APIIDA Reports Data class
 *
 * @package erikdmitchell\bcmigration\apiida
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\apiida;

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
     * @var bool
     */
    private static $instance = false;


    /**
     * Constructor.
     *
     * Initializes the MigrateReports class by setting up the upload directory path
     * and database instance for report data.
     *
     * @access private
     */
    private function __construct() { // TODO: same as MigrateReports
        $upload_dir = wp_upload_dir();

        $this->upload_dir_path = $upload_dir['basedir'];
        $this->db      = \BoomiCMS\BC_DB::getInstance()->report_data();
    }

    /**
     * Gets the single instance of the class.
     *
     * @return MigrateReports Single instance of the class.
     */
    public static function init() { // TODO: same as MigrateReports
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
    public function migrate_data() { // TODO: same as MigrateReports
        $files = $this->get_files();

        if ( empty( $files ) ) {
            return array();
        }

        return $this->migrate_reports( $files );
    }


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

    /*
    $log = boomi_get_logger();

    $filename = pathinfo($data['file'], PATHINFO_FILENAME);        
    $prepared_data = $this->prepare_data_for_db($data['file'], $data['app']);
    $email = isset($prepared_data['email']) ? $prepared_data['email'] : 'no email';

    // TODO: needs to be dynamic
    $report_url = site_url() . '/api-management-maturity-assessment/#report-' . $filename;
    $prepared_data['report_url'] = $report_url;
  
    if ($this->db_entry_exists($data['db'], $prepared_data)) {
        $log->warning(
            sprintf(
                __( 'DB entry already exists for %s', 'boomi-cms' ), $email
            ),
            array(
                'source' => 'bc-legacy-tool-reports',
                'data'   => $data,
            )
        );

        return false;
    }

    $db_id = $this->insert_into_db($prepared_data, $data['db']);

    if ( is_wp_error( $db_id ) ) {
        $log->error(
            sprintf(
                __( 'Failed to insert data', 'boomi-cms' ),
            ),
            array(
                'source' => 'bc-legacy-tool-reports',
                'data'   => $data,
                'prepared_data' => $prepared_data,
            )
        );

        return false;
    }

    $log->info(
        sprintf(
            __( 'Inserted data for %s', 'boomi-cms' ), $email
        ),
        array(
            'source' => 'bc-legacy-tool-reports',
            'data'   => $data,
        )
    );

    return false;
    */


    private function prepare_report_for_db( string $file, string $app ) {
        $data        = (array) json_decode( file_get_contents( $file ) );
        $data        = $this->maybe_format_keys( $data );
        $data        = $this->prepare_item_for_db( $data );
        $data['title']      = isset( $data['job'] ) ? $data['job'] : null;
        $data['report_url'] = $data['boomi_link'];        
        $data['app'] = $app;
    
        if ( isset( $data['job'] ) ) {
            unset( $data['job'] );
        }

        unset( $data['boomi_link'] );
    
        return $data;
    }

    /**
     * Converts the keys of the given associative array from camel case to snake case.
     *
     * Utilizes the `bc_camel_to_snake` function to transform each key in the array.
     *
     * @param array $data The associative array with camel case keys to be transformed.
     * @return array The array with keys converted to snake case.
     */
    private function maybe_format_keys( array $data ) { // TODO: same as MigrateReports
        return array_combine(
            array_map( 'bc_camel_to_snake', array_keys( $data ) ),
            array_values( $data )
        );
    }


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
    
    /*
    private function prepare_data_for_db(string $file, string $app) {        
        $data = (array) json_decode(file_get_contents($file));
        $data = $this->maybe_format_keys($data);
        $data = $this->prepare_item_for_db($data);
        $data['app'] = $app;

        return $data;
    }
    */

/*
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

    /*
    private function db_entry_exists( object $db, array $data ) {
        if (!isset($data['first_name']) || !isset($data['last_name']) || !isset($data['email'])) {
            return true; // the data is not valid, so we need to skip it anyway.
        }

        if ( empty( $data['first_name'] ) || empty( $data['last_name'] ) || empty( $data['email'] ) ) {
            return true; // the data is not valid, so we need to skip it anyway.
        }

        $exits = $db->get_column_by_where( 'id', array( 'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $data['email'] ) );

        if ( ! empty( $exits ) ) {
            return true;
        }

        return false;
    }
    */


    private function insert_into_db( array $data ) {
        $db_id = $this->db->add( $data );

        if ( ! $db_id ) {
            return new WP_Error(
                'aitool_reports_insert_into_db',
                'Failed to insert data',
            );
        }

        return $db_id;
    }

    /*
    private function insert_into_db( array $data, object $db ) {        
        $db_id = $db->add( $data );

        if ( ! $db_id ) {
            return new WP_Error( 'legacy_tool_reports_insert_into_db', 'Failed to insert data', array( 
                'data' => $data,
                'db' => $db,
            ) );
        }

        return $db_id;
    }
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

            // Encode the modified data back to JSON and save it to the file
            file_put_contents( $file, json_encode( $data, JSON_PRETTY_PRINT ) );
        }

        $files = array_values( $files );

        return $files;
    }
}