<?php
/**
 * AI Tool CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use erikdmitchell\bcmigration\aitool\MigrateLikes;
use erikdmitchell\bcmigration\aitool\MigrateReports;
use WP_CLI;

/**
 * AITool CLI class.
 */
class AITool extends CLICommands {

    /**
     * Migrates AI Tool data.
     *
     * ## OPTIONS
     *
     * <action>
     * : The action to perform. Currently only `reports` and `likes` are supported.
     *
     * ## EXAMPLES
     *
     *     # Migrate AI Tool reports.
     *     $ wp migrate reports
     *     Success: Migrated 13 reports.
     *
     *     # Migrate AI Tool likes.
     *     $ wp migrate likes
     *     Success: Migrated 13 likes.
     *
     *     # Remove the AI Tool uploads folder.
     *     $ wp migrate remove-folder
     *     Success: Removed the AI Tool uploads folder.
     */
    public function migrate( $args, $assoc_args ) {
        list ( $action ) = $args;

        if (empty( $action ) ) {
            WP_CLI::error( 'Invalid arguments. Requires action and post_id' );
        }

        switch ( $action ) {
            case 'reports':
                $this->migrate_reports();
                break;
            case 'likes':
                $this->migrate_likes();
                break;
            case 'remove-folder':
                $this->remove_folder();
                break;
            default:
                WP_CLI::error( 'Invalid action.' );
                break;
        }
    }

    /**
     * Migrates AI Tool reports to the database.
     *
     * This method will log messages to the user about the migration process and
     * report the number of migrated records.
     */
    private function migrate_reports() {
        WP_CLI::log( 'Migrating AI Tool reports...' );

        $migrated_data = MigrateReports::init()->migrate_data();

        if ( empty( $migrated_data ) ) {
            WP_CLI::log( 'No data to migrate.' );

            return;
        }

        WP_CLI::success( count( $migrated_data ) . ' AI Tool reports migrated successfully.' );
    }

    /**
     * Migrates AI Tool likes to the database.
     *
     * This method will log messages to the user about the migration process and
     * report the number of migrated records.
     */
    private function migrate_likes() {
        WP_CLI::log( 'Migrating AI Tool likes...' );

        $migrated_data = MigrateLikes::init()->migrate_data();

        if ( empty( $migrated_data ) ) {
            WP_CLI::log( 'No data to migrate.' );

            return;
        }

        WP_CLI::success( count( $migrated_data ) . ' AI Tool likes migrated successfully.' );        
    }

    /**
     * Removes the AI Tool uploads folder.
     *
     * This method will log messages to the user about the deletion process.
     *
     * @since 0.1.0
     */
    private function remove_folder() {
        $upload_dir = wp_upload_dir( null, false );
        $deleted = $this->remove_directory( $upload_dir['basedir'] . '/bc-ai-tool-data' );

        if ( $deleted ) {
            WP_CLI::success( 'AI Tool uploads folder deleted.' );

            delete_option( '_bc_ai_tool_page_id' );
        } else {
            WP_CLI::error( 'AI Tool uploads folder deletion failed.' );
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
