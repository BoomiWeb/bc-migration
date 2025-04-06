<?php
/**
 * Setups the base for CLI commands functionality
 *
 * @package     erikdmitchell\bcmigration\Abstracts
 * @since     0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\abstracts;

use WP_CLI;

/**
 * CLI Commands
 */
abstract class CLICommands {

    /**
     * Upload path
     *
     * @var string
     */
    public $upload_path;

    /**
     * Upload URL
     *
     * @var string
     */
    public $upload_url;

    /**
     * WP CLI notices.
     *
     * @var array
     */
    public $notices = array();

    /**
     * Set the upload path.
     * If the path is a directory, it will create it if it does not exist.
     *
     * @param string $path The complete path to use.
     * @return void
     */
    public function set_upload_path( $path = '' ) {
        $this->upload_path = $path;
        $this->create_dir( $path );
    }

    /**
     * Set the upload URL.
     *
     * @param string $url The full URL to use.
     * @return void
     */
    public function set_upload_url( $url = '' ) {
        $this->upload_url = $url;
    }

    /**
     * Get the upload path.
     *
     * @return string The full path.
     */
    public function get_upload_path() {
        return $this->upload_path;
    }

    /**
     * Get the upload URL.
     *
     * @return string The full URL.
     */
    public function get_upload_url() {
        return $this->upload_url;
    }

    /**
     * Outputs a message to the CLI.
     *
     * @param string $message The message to display.
     * @param string $type The type of message. Can be 'info', 'success', 'warning', 'error'.
     *                     Defaults to 'info'.
     * @return void
     */
    public function output( string $message = '', string $type = 'info' ) {       
        if ( empty( $message ) ) {
            return;
        }

        if ( ! in_array( $type, array( 'info', 'success', 'warning', 'error' ) ) ) {
            $type = 'info';
        }

        switch ( $type ) {
            case 'info':
                WP_CLI::log( $message );
                break;
            case 'success':
                WP_CLI::success( $message );
                break;
            case 'warning':
                WP_CLI::warning( $message );
                break;
            case 'error':
                WP_CLI::error( $message );
                break;
        }
    }

    /**
     * Create dir if does not exist.
     *
     * @param string $path The directory path.
     * @return void
     */
    private function create_dir( $path ) {
        if ( ! is_dir( $path ) ) {
            mkdir( $path );
        }
    }

    /**
     * Create and write to a CSV file
     *
     * @param string $filename the full name of the file.
     * @param array  $rows array of data.
     * @param array  $headers (default: array()).
     * @return void
     */
    protected function export_csv( $filename, $rows, $headers = array() ) {
        $return = array();

        if ( file_exists( $filename ) ) {
            $this->add_notice( 'warning', "File already exists. The following file will be overwritten $filename" );
        }

        $fp = fopen( $filename, 'w+' ); // @codingStandardsIgnoreLine WordPress.WP.AlternativeFunctions.file_system_read_fopen

        if ( ! empty( $headers ) ) {
            fputcsv( $fp, $headers );
        }

        foreach ( $rows as $row ) {
            if ( ! empty( $headers ) ) {
                $row = $this->pick_fields( $row, $headers );
            }

            fputcsv( $fp, array_values( $row ) );
        }

        fclose( $fp ); // @codingStandardsIgnoreLine WordPress.WP.AlternativeFunctions.file_system_read_fclose

        $this->add_notice( 'success', "File created: $filename" );
    }

    /**
     * Pick fields from an associative array or object.
     *
     * @param  array|object $item    Associative array or object to pick fields from.
     * @param  array        $fields  List of fields to pick.
     * @return array
     */
    private function pick_fields( $item, $fields ) {
        $values = array();

        if ( is_object( $item ) ) {
            foreach ( $fields as $field ) {
                $values[ $field ] = isset( $item->$field ) ? $item->$field : null;
            }
        } else {
            foreach ( $fields as $field ) {
                $values[ $field ] = isset( $item[ $field ] ) ? $item[ $field ] : null;
            }
        }

        return $values;
    }

    /**
     * Add a notice for output.
     *
     * @link https://make.wordpress.org/cli/handbook/references/internal-api/#output
     *
     * @param string $type The type of notice (see link).
     * @param string $message The message to display.
     * @return void
     */
    public function add_notice( $type = '', $message = '' ) {
        if ( '' === $type || '' === $message ) {
            return;
        }

        $this->notices[] = array(
            'type'    => $type,
            'message' => $message,
        );
    }

    /**
     * Get all notices.
     *
     * @return array The array of notices.
     */
    public function get_notices() {
        return $this->notices;
    }

    /**
     * Display the notices. The output is WP_CLI.
     *
     * @return void
     */
    public function display_notices() {
        if ( empty( $this->notices ) ) {
            return;
        }

        foreach ( $this->notices as $notice ) {
            WP_CLI::{$notice['type']}( $notice['message'] );
        }
    }

    /**
     * Reset all notices.
     *
     * @return void
     */
    public function reset_notices() {
        $this->notices = array();
    }
}
