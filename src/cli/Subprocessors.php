<?php
/**
 * Subprocessors CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use erikdmitchell\bcmigration\subprocessor\MigrateSubscribeData;
use WP_CLI;

/**
 * Subprocessors CLI class.
 */
class Subprocessors extends CLICommands {

    /**
     * Construct
     */
    public function __construct() {}

    /**
     * Migrates data from a post
     *
     * ## OPTIONS
     *
     * <action>
     * : The action to perform. Currently only `subscribe-data` is supported.
     *
     * <post_id>
     * : The post ID to migrate data from.
     *
     * ## EXAMPLES
     *
     *     wp boomi migrate subprocessors subscribe-data 123
     *
     * @subcommand migrate
     */
    public function migrate( $args, $assoc_args ) {
        list ( $action, $post_id ) = $args;

        if ( empty( $action ) || empty( $post_id ) ) {
            WP_CLI::error( 'Invalid arguments. Requires action and post_id' );
        }

        switch ( $action ) {
            case 'subscribe-data':
                $this->migrate_subscribe_data( (int) $post_id );
                break;
            default:
                WP_CLI::error( 'Invalid action.' );
                break;
        }
    }

    /**
     * Migrates subscribe data for a given post ID, and removes the legacy email DB table.
     *
     * @param int $post_id The ID of the post whose subscribe data should be migrated.
     */
    private function migrate_subscribe_data( int $post_id ) {
        WP_CLI::log( 'Migrating subscribe data...' );

        $migrated_data = MigrateSubscribeData::init()->migrate_subscribe_data( $post_id );

        if ( ! $migrated_data['db_removed'] ) {
            WP_CLI::warning( 'Database not removed' );
        }

        WP_CLI::success( 'Data migrated. Migrated ' . count( $migrated_data['migrated_row_ids'] ) . ' rows.' );
    }
}
