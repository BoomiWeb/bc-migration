<?php
/**
 * Migrate Subscribe Data class
 *
 * @package erikdmitchell\bcmigration\subprocessor
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\subprocessor;

/**
 * MigrateSubscribeData class.
 */
class MigrateSubscribeData {

    /**
     * The name of the email database table.
     *
     * @var string
     */
    private $db_table;

    /**
     * The database object for the `subscribe_to_page` table.
     *
     * @var object
     */
    private $stp_db;

    /**
     * The single instance of the class.
     *
     * @var MigrateSubscribeData|null
     */
    protected static ?MigrateSubscribeData $instance = null;

    /**
     * Initializes the class and sets the database properties.
     *
     * @internal
     */
    private function __construct() {
        global $wpdb;

        $this->db_table = $wpdb->prefix . 'boomi_subprocessors_email';
        $this->stp_db   = \BoomiCMS\BC_DB::getInstance()->subscribe_to_page();
    }

    /**
     * Gets the single instance of the class.
     *
     * @return MigrateSubscribeData Single instance of the class.
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Migrates subscribe data for a given post ID, and removes the legacy email DB table.
     *
     * @param int $post_id The ID of the post whose subscribe data should be migrated.
     * @return array{migrated_row_ids: array<int>, db_removed: int}|void An associative array containing two values:
     *               - 'migrated_row_ids' - an array of IDs of migrated rows (may be empty)
     *               - 'db_removed' - int indicating whether the email DB table was removed
     */
    public function migrate_subscribe_data( int $post_id = 0 ) {
        if ( ! $post_id ) {
            return;
        }

        $migrated_row_ids = $this->migrate_email_db( array( $post_id ) ); // array - may be empty.
        $remove_db        = $this->remove_email_db(); // 1|0
        $this->remove_email_options();

        return array(
            'migrated_row_ids' => $migrated_row_ids,
            'db_removed'       => $remove_db,
        );
    }

    /**
     * Migrates the `bcm_subprocessors_email` table to the `subscribe_to_page` table.
     *
     * @param int[] $posts Array of post IDs to migrate.
     *
     * @return int[] Array of migrated row IDs.
     */
    private function migrate_email_db( array $posts ) {
        global $wpdb;

        if ( ! $this->db_table_exists() ) {
            return array();
        }

        $migrated_rows = array();
        $db_result     = $wpdb->get_results( "SELECT * FROM $this->db_table" );

        if ( null === $db_result ) {
            return $migrated_rows;
        }

        foreach ( $db_result as $row ) {
            $db_id = $this->stp_db->add(
                array(
                    'email'   => $row->email,
                    'posts'   => \maybe_serialize( $posts ),
                    'created' => $row->created,
                )
            );

            if ( false !== $db_id ) {
                $migrated_rows[] = $db_id;
            }
        }

        return $migrated_rows;
    }

    /**
     * Removes the email database.
     *
     * Drops the table if it exists, then deletes the database version option.
     *
     * @return int The number of rows deleted.
     */
    private function remove_email_db() {
        global $wpdb;

        $deleted = $wpdb->query( "DROP TABLE IF EXISTS $this->db_table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        \delete_option( 'wp_boomi_subprocessors_email_db_version' );

        return $deleted;
    }

    /**
     * Deletes the options set by the subscribe form.
     *
     * This deletes the options for the subscribe emails and settings.
     *
     * @return void
     */
    private function remove_email_options() {
        \delete_option( '_bc_subprocessors_subscribe_emails' );
        \delete_option( '_bc_subprocessors_subscribe_settings' );
    }

    /**
     * Check if the email table exists in the database.
     *
     * @return bool Whether the table exists.
     */
    private function db_table_exists() {
        global $wpdb;

        return $wpdb->get_var( "SHOW TABLES LIKE '{$this->db_table}'" );
    }
}
