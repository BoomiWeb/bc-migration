<?php

/**
 * Migrate subprocessor subscribe emails for the 4.4.0 update.
 *
 * @return void
 */
function bc_update_440_migrate_subprocessor_subscribe_emails() {
    // only run if admin.
    if ( ! is_admin() ) {
        return;
    }

    $current_user = wp_get_current_user();

    // only run if the user is the admin.
    if ( get_bloginfo( 'admin_email' ) !== $current_user->user_email ) {
        return;
    }

    // only run if the process is not already running.
    if ( get_site_transient( 'wp_shortcode_search_process_process_lock' ) ) {
        return;
    }

    // only run if the process is not already complete.
    if ( 'no' === get_option( '_bc_migrate_subprocessors_emails_complete' ) ) {
        Boomi_Admin_Notices::add(
            'boomi_bg_process_test',
            'To complete the CMS 4.4.0 version upgrade, please click <a href="' . wp_nonce_url( admin_url( '?bc_sc_search_process=run&shortcode=subprocessors-subscribe' ), 'bc_bg_process' ) . '">here</a>.',
            'warning',
            true
        );
    }
}

/**
 * Migrate the subprocessor subscribe data for the 4.4.0 update.
 * This is triggered from the background process.
 * It will update the DB and options, remove the old database, and remove the old options.
 *
 * @return void
 */
function bc_update_440_migrate_subprocessor_subscribe_data() {
    $bc_fs      = BC_Filesystem::getInstance();
    $log        = boomi_get_logger();
    $upload_dir = wp_upload_dir();
    $file_path  = $upload_dir['path'] . '/_bc_tmp/bc_shortcode_search.json';

    if ( bc_file_exists( $file_path ) ) {
        $data = json_decode( $bc_fs->read( $file_path ), true );
        $bc_fs->delete( $file_path );
    } else {
        $data = array();

        return;
    }

    if ( ! is_array( $data ) || empty( $data ) || ! isset( $data['posts'] ) ) {
        return;
    }

    $migrated_row_ids = bc_update_440_migrate_subprocessors_email_db( $data['posts'] );
    $remove_db        = bc_update_440_remove_subprocessors_email_db();

    bc_update_440_remove_subprocessors_email_options();

    $log->info(
        __( 'Subprocessors subscribe emails have been migrated.', 'boomi-cms' ),
        array(
            'source'           => 'bc_update_440_migrate_subprocessor_subscribe_data',
            'posts'            => $data['posts'],
            'migrated_row_ids' => $migrated_row_ids,
            'remove_db'        => $remove_db,
        )
    );
}
add_action( 'bc_shortcode_search_process_complete', 'bc_update_440_migrate_subprocessor_subscribe_data' );

/**
 * Migrates subprocessors email data from the database to the subscribe_to_page table.
 *
 * @param array $posts An array of posts to associate with the migrated email data.
 * @return array An array of IDs of the migrated rows in the subscribe_to_page table.
 */
function bc_update_440_migrate_subprocessors_email_db( $posts = array() ) {
    global $wpdb;

    $subprocessors_email_table = $wpdb->prefix . 'boomi_subprocessors_email';
    $stp_db                    = BoomiCMS\BC_DB::getInstance()->subscribe_to_page();
    $stp_db_migrated_rows      = array();
    $subp_email_cache_key      = 'boomi_subprocessors_email';
    $_posts                    = wp_cache_get( $subp_email_cache_key );
    $db_result                 = null;

    if ( false === $_posts ) {
        $db_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->prepare(
                "SELECT * FROM $subprocessors_email_table" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            )
        );

        wp_cache_set( $subp_email_cache_key, $db_result, '', 60 );
    }

    if ( null === $db_result ) {
        return $stp_db_migrated_rows;
    }

    foreach ( $db_result as $row ) {
        $db_id = $stp_db->add(
            array(
                'email'   => $row->email,
                'posts'   => maybe_serialize( $posts ),
                'created' => $row->created,
            )
        );

        if ( false !== $db_id ) {
            $stp_db_migrated_rows[] = $db_id;
        }
    }

    return $stp_db_migrated_rows;
}

/**
 * Removes the subprocessors email database table and deletes the corresponding option.
 *
 * @global wpdb $wpdb WordPress database object.
 * @return bool True if the table was deleted successfully, false otherwise.
 */
function bc_update_440_remove_subprocessors_email_db() {
    global $wpdb;

    $subprocessors_email_table = $wpdb->prefix . 'boomi_subprocessors_email';

    $deleted = $wpdb->query( "DROP TABLE IF EXISTS $subprocessors_email_table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    delete_option( 'wp_boomi_subprocessors_email_db_version' );

    return $deleted;
}

/**
 * Removes the subprocessors email options.
 *
 * @return void
 */
function bc_update_440_remove_subprocessors_email_options() {
    delete_option( '_bc_subprocessors_subscribe_emails' );
    delete_option( '_bc_subprocessors_subscribe_settings' );
}