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
use erikdmitchell\bcmigration\aitool\MigrateReports;
use WP_CLI;

/**
 * AITool CLI class.
 */
class AITool extends CLICommands {

    /**
     * Construct
     */
    public function __construct() {

        // $this->reports_db      = BC_DB::getInstance()->report_data();
        // $this->likes_db        = BC_DB::getInstance()->likes_data();
        

        // add_action( 'bc_legacy_tool_reports_process_complete_aitool', array( $this, 'migrate_aitool_likes_data' ), 1 );
        // add_action( 'bc_legacy_tool_likes_process_complete_aitool', array( $this, 'remove_ai_tool_uploads_folder' ) );

        // add_filter( 'bc_legacy_tool_reports_prepare_data_for_db_' . 'aitool', array( $this, 'bg_process_prepare_aitool_data_for_db' ), 10, 3 );        
    }

    public function migrate( $args, $assoc_args ) {
        list ( $action ) = $args;

        if (empty( $action ) ) {
            WP_CLI::error( 'Invalid arguments. Requires action and post_id' );
        }

        switch ( $action ) {
            case 'reports':
                $this->migrate_reports();
                break;
            default:
                WP_CLI::error( 'Invalid action.' );
                break;
        }
    }

    private function migrate_reports() {
WP_CLI::log( 'Migrating reports...' );

        MigrateReports::init()->migrate_data();
    }



 
    
    public function remove_ai_tool_uploads_folder() {
        error_log( 'bc_update_521_remove_ai_tool_uploads_folder - false' );
        return false;
        $upload_dir = wp_upload_dir( null, false );
        $bc_logger  = boomi_get_logger();

        $deleted = BC_Filesystem::getInstance()->delete( $upload_dir['basedir'] . '/bc-ai-tool-data' );

        if ( $deleted ) {
            $bc_logger->info(
                sprintf(
                    __( 'AI Tool uploads folder deleted.', 'boomi-cms' )
                ),
                array(
                    'source' => 'boomi-ai-tool-uploads-folder-update',
                )
            );
        } else {
            $bc_logger->error(
                sprintf(
                    __( 'AI Tool uploads folder deletion failed.', 'boomi-cms' )
                ),
                array(
                    'source' => 'boomi-ai-tool-uploads-folder-update',
                )
            );
        }

        delete_option( '_bc_ai_tool_page_id' );
    }
    

    

    


}
