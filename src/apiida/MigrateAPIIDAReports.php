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
        parent::__construct();
    }

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

    protected function get_files() {     
        $data_files = array();
        $path = $this->upload_dir_path . '/bc-apiida/results';

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

    protected function migrate_reports( array $files ) {        
        $migrated_reports = array();

        if ( empty( $files ) ) {
            return false;
        }

        foreach ( $files as $file ) {
            $prepared_data = $this->prepare_report_for_db( $file, 'apiida' ); // TODO: same as MigrateReports - this is the only diff

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


    private function prepare_report_for_db( string $file, string $app ) {
        $data        = (array) json_decode( file_get_contents( $file ) );
        $data        = $this->maybe_format_keys( $data );
        $data        = $this->prepare_item_for_db( $data );

        $data['title']      = isset( $data['job'] ) ? $data['job'] : null;        
        $data['app'] = $app;
    
        if ( isset( $data['job'] ) ) {
            unset( $data['job'] );
        }

        return $data;
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

            $filename = pathinfo($file, PATHINFO_FILENAME);
            $report_url = site_url() . '/api-management-maturity-assessment/#report-' . $filename;
        
            $data['report_url'] = $report_url;

            // Encode the modified data back to JSON and save it to the file
            file_put_contents( $file, json_encode( $data, JSON_PRETTY_PRINT ) );
        }

        $files = array_values( $files );

        return $files;
    }
}
