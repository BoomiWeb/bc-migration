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
use erikdmitchell\bcmigration\aitool\MigrateReports;
use erikdmitchell\bcmigration\apiida\MigrateAPIIDAReports;
use WP_CLI;

/**
 * APIIDA CLI class.
 */
class APIIDA extends CLICommands {

    public function migrate( $args, $assoc_args ) {
        list ( $action ) = $args;

        if (empty( $action ) ) {
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


    private function migrate_reports() {
        WP_CLI::log( 'Migrating APIIDA reports...' );

        $migrated_data = MigrateAPIIDAReports::init()->migrate_data();

        if ( empty( $migrated_data ) ) {
            WP_CLI::log( 'No data to migrate.' );

            return;
        }

        WP_CLI::success( count( $migrated_data ) . ' APIIDA reports migrated successfully.' );
    }

    private function remove_folder() {
        $upload_dir = wp_upload_dir( null, false );
        $deleted = $this->remove_directory( $upload_dir['basedir'] . '/bc-apiida' );

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
     *
     * @return bool True if the directory was deleted successfully, false otherwise.
     */
    private function remove_directory(string $dir) {
        if (!file_exists($dir)) {
            return true;
        }
    
        if (!is_dir($dir)) {
            return unlink($dir);
        }
    
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
    
            if (!$this->remove_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
    
        }
        
        return rmdir($dir);        
    }

}