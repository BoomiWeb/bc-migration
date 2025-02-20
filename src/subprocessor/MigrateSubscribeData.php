<?php

namespace erikdmitchell\bcmigration;

class MigrateSubscribeData {

    public function __construct() {
        echo 'migrate subscribe data';
        // add_action( 'bc_shortcode_search_process_complete', 'bc_update_440_migrate_subprocessor_subscribe_data' );
    }

    public function init() {}

    private function migrate_subscribe_emails() {
        // only run if the process is not already running.
        if ( get_site_transient( 'wp_shortcode_search_process_process_lock' ) ) {
            return;
        }

        // only run if the process is not already complete.
        if ( 'no' === get_option( '_bc_migrate_subprocessors_emails_complete' ) ) {
            ShortcodeSearch::get_instance()->process_shortcode_search( 'subprocessors-subscribe' );
        }        
    }

    private function migrate_subprocessor_subscribe_data() {
        $migrated_row_ids = $this->migrate_subprocessors_email_db( $data['posts'] );
        $remove_db        = $this->remove_subprocessors_email_db();
    
        $this->remove_subprocessors_email_options();

        return 'data';
    
        // $log->info(
        //     __( 'Subprocessors subscribe emails have been migrated.', 'boomi-cms' ),
        //     array(
        //         'source'           => 'bc_update_440_migrate_subprocessor_subscribe_data',
        //         'posts'            => $data['posts'],
        //         'migrated_row_ids' => $migrated_row_ids,
        //         'remove_db'        => $remove_db,
        //     )
        // );        
    }

    private function migrate_subprocessors_email_db( $posts = array() ) {
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
    
    private function remove_subprocessors_email_db() {
        global $wpdb;
    
        $subprocessors_email_table = $wpdb->prefix . 'boomi_subprocessors_email';
    
        $deleted = $wpdb->query( "DROP TABLE IF EXISTS $subprocessors_email_table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    
        delete_option( 'wp_boomi_subprocessors_email_db_version' );
    
        return $deleted;
    }  
    
    private function remove_subprocessors_email_options() {
        delete_option( '_bc_subprocessors_subscribe_emails' );
        delete_option( '_bc_subprocessors_subscribe_settings' );
    }

}