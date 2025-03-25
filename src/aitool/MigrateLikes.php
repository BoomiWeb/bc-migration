<?php
/**
 * Migrate Likes Data class
 *
 * @package erikdmitchell\bcmigration\aitool
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\aitool;

use WP_Error;

/**
 * MigrateLikes class.
 */
class MigrateLikes {

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
    private function __construct() {
        $upload_dir = wp_upload_dir();

        $this->upload_dir_path = $upload_dir['basedir'];
        $this->db              = \BoomiCMS\BC_DB::getInstance()->likes_data();
    }

    /**
     * Gets the single instance of the class.
     *
     * @return MigrateLikes Single instance of the class.
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

        return $this->migrate_likes( $files );
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
            if ( strpos( basename( $file ), 'likes-data_' ) !== false ) {
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
     * Migrates AI Tool like data from a set of JSON files to the database.
     *
     * Takes an array of file paths and migrates the data in each file to the database.
     * If the file does not exist, or if the data is not in the correct format, the function
     * skips it and continues to the next file.
     *
     * @param array $files Array of file paths to migrate.
     *
     * @return array Array of migrated row IDs.
     */
    private function migrate_likes( array $files ) {
        $migrated_likes = array();

        if ( empty( $files ) ) {
            return false;
        }

        foreach ( $files as $file ) {
            $prepared_data = $this->prepare_data_for_db( $file );

            if ( empty( $prepared_data ) ) {
                continue;
            }

            $db_id = $this->insert_or_update_db( $prepared_data );

            if ( is_wp_error( $db_id ) ) {
                continue;
            }

            $migrated_likes[] = $db_id;
        }

        return $migrated_likes;
    }

    /**
     * Prepares the given data for insertion into the database.
     *
     * Converts the json file to an array, formats the keys from camel case to snake case,
     * prepares the item for the database by setting the created date to the current time if not provided,
     * and then adds the app name to the data.
     *
     * @param string $file The json file to read.
     *
     * @return array The prepared data.
     */
    private function prepare_data_for_db( string $file ) {
        $data         = (array) json_decode( file_get_contents( $file ) );
        $data         = $this->maybe_format_keys( $data );
        $data         = $this->prepare_item_for_db( $data );
        $data['tool'] = 'ai-tool';

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
    private function maybe_format_keys( array $data ) {
        return array_combine(
            array_map( 'bc_camel_to_snake', array_keys( $data ) ),
            array_values( $data )
        );
    }

    /**
     * Prepares the given item for insertion into the database.
     *
     * Sets the created date to the current time if not provided.
     *
     * @param array $item The item to prepare.
     * @return array The prepared item.
     */
    private function prepare_item_for_db( array $item ) {
        if ( ! isset( $item['created'] ) || empty( $item['created'] ) ) {
            $item['created'] = current_time( 'mysql' );
        }

        return $item;
    }

    /**
     * Inserts or updates the given data into the database.
     *
     * If the data exists in the database, it is updated. Otherwise, it is inserted.
     *
     * @param array $data The data to insert or update.
     *
     * @return int|WP_Error The id of the created or updated row, or a WP_Error object on failure.
     */
    private function insert_or_update_db( array $data ) {
        $db_entry = $this->db_entry_exists( $data );

        if ( $db_entry ) {
            $updated = $this->update_db( $db_entry, $data );

            if ( false === $updated ) {
                return new WP_Error( 'aitool_rlikes_insert_or_update_db', 'Unable to update likes data to the db.', $data );
            }

            return $db_entry->id;
        }

        $db_id = $this->db->add( $data );

        if ( ! $db_id ) {
            return new WP_Error(
                'aitool_rlikes_insert_or_update_db',
                'Failed to insert data',
                array(
                    'data' => $data,
                    'db'   => $db,
                )
            );
        }

        return $db_id;
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
        $db_data = $this->db->get(
            array(
                'category' => $data['category'],
                'answer'   => $data['answer'],
                'tool'     => $data['tool'],
                'limit'    => 1,
            )
        );

        if ( empty( $db_data ) ) {
            return false;
        }

        return $db_data;
    }

    /**
     * Updates the like or not_like count in the database.
     *
     * @param object $db_data The existing database row data.
     * @param array  $data    The data indicating whether it's a like or not like.
     *
     * @return bool True if the update is successful, false otherwise.
     */
    private function update_db( $db_data, array $data ) {
        if ( $data['like'] ) {
            $db_data->like = $db_data->like + 1;
        } else {
            $db_data->not_like = $db_data->not_like + 1;
        }

        return $this->db->update(
            $db_data->id,
            array(
                'like'     => isset( $db_data->like ) ? $db_data->like : 0,
                'not_like' => isset( $db_data->not_like ) ? $db_data->not_like : 0,
            )
        );
    }

    /**
     * Clean up the given files.
     *
     * @param array $files The files to clean up.
     *
     * @return array The cleaned up files.
     */
    private function clean_files( array $files ) {
        foreach ( $files as $file ) {
            // Decode the JSON file.
            $data = json_decode( file_get_contents( $file ), true );

            if ( ! isset( $data['category'] ) || ! isset( $data['answer'] ) ) {
                unset( $files[ $file ] );
                continue;
            }

            if ( null === $data['like'] ) {
                $data['not_like'] = 1;
                $data['like']     = 0;
            } else {
                $data['like']     = 1;
                $data['not_like'] = 0;
            }

            if ( strpos( $data['answer'], '1' ) !== false ) {
                $data['answer'] = 1;
            } else {
                $data['answer'] = (int) $data['answer'];
            }

            // Encode the modified data back to JSON and save it to the file.
            file_put_contents( $file, wp_json_encode( $data, JSON_PRETTY_PRINT ) );
        }

        $files = array_values( $files );

        return $files;
    }
}
