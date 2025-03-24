<?php
/**
 * Legacy Tool Reports
 *
 * @package BoomiCMS\Tools
 * @since   5.1.2
 * @version 0.1.0
 */

namespace BoomiCMS\Tools;

use BoomiCMS\BC_DB;
use BoomiCMS\BC_Filesystem;

class LegacyToolReports {

    /**
     * The single instance of this class
     *
     * @var LegacyToolReports
     */
    private static $instance;

    /**
     * The path to the upload directory
     *
     * @var string
     */
    private $upload_dir_path;

    /**
     * The database object
     *
     * @var object
     */
    private $reports_db;

    /**
     * The database object
     *
     * @var object
     */
    private $likes_db;

    /**
     * The background process
     *
     * @var object
     */
    private $bg_process;

    /**
     * Get the singleton instance of this class.
     *
     * @since 5.1.2
     * @return LegacyToolReports The instance.
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 5.1.2
     *
     * @return void
     */
    private function __construct() {
error_log( 'LegacyToolReports.php' );        



    }

    /**
     * Migrates the AI Tool data from the uploads folder to the new table.
     *
     * This method will only run if the transient for the reports process is not set, which means
     * the reports process has not been initiated yet. If the transient is set, it means the reports
     * process is already running and we don't want to initiate it again.
     *
     * @since 5.1.2
     * @return void
     */


    /**
     * Migrates the AI Tool likes data from the uploads folder to the new table.
     *
     * This method will only run if the transient for the likes process is not set, which means
     * the likes process has not been initiated yet. If the transient is set, it means the likes
     * process is already running and we don't want to initiate it again.
     *
     * @since 5.1.2
     * @return void
     */


    /**
     * Migrates the results of the APIIDA tool to the new database.
     *
     * @since 5.1.2
     *
     * @return void
     */
    public function migrate_apiida() {
        return;
        $files = array();
        $path  = $this->upload_dir_path . '/bc-apiida/results';

        if ( ! is_dir( $path ) ) {
            return;
        }

        foreach ( glob( $path . '*.json' ) as $file ) {
            $files[] = $file;
        }

        if ( empty( $files ) ) {
            return;
        }

        $this->bg_process->process( $files, $this->reports_db, 'apiida' );

        return;
    }



    /**
     * Prepares AI tool data for the database by modifying and removing specific fields.
     *
     * Sets the 'title' to the 'job' value if present, and assigns 'boomi_link' to 'report_url'.
     * Removes the 'job' and 'boomi_link' fields from the data array.
     *
     * @param array  $data The data to be processed.
     * @param string $file The file associated with the data.
     * @param string $app  The application name for which the data is being processed.
     *
     * @return array The processed data ready for database insertion.
     */


    /**
     * Clean up AI tool data files by adding a default Boomi link and removing files with missing required fields.
     *
     * @param array $files The AI tool data files to be cleaned.
     *
     * @return array The cleaned AI tool data files.
     */


    /**
     * Cleans up AI tool likes data files by ensuring required fields are present and updating data structure.
     *
     * Iterates through each provided file, decoding JSON data and checking for the presence of 'category' and 'answer' fields.
     * Files lacking these fields are removed from the list. The function initializes or updates 'like' and 'not_like' fields
     * based on the presence of a 'like' value. Furthermore, it converts the 'answer' field to an integer or sets it to 1
     * if it contains the string '1'. After modifications, the JSON data is encoded back and written to the file.
     *
     * @param array $files The AI tool likes data files to be cleaned.
     *
     * @return array The cleaned AI tool likes data files.
     */

}
