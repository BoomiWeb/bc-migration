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
use WP_CLI;

/**
 * Subprocessors CLI class.
 */
class Subprocessors extends CLICommands {

    /**
     * Construct
     */
    public function __construct() {}

    public function migrate( $args, $assoc_args ) {
        list ( $action ) = $args;

        switch ( $action ) {
            case 'subscribe-data':
                $this->migrate_subscribe_data();
                break;
            default:
                WP_CLI::error( 'Invalid action.' );
                break;
        }
    }

    private function migrate_subscribe_data() {
        WP_CLI::log( 'Migrating subscribe data.' );
        // $migrate = new MigrateSubscribeData();
        // $migrate->migrate();
    }

}
