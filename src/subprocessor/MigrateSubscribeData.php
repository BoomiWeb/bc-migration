<?php

namespace erikdmitchell\bcmigration\subprocessor;

class MigrateSubscribeData {

    private $db_table;

    private $stp_db;

    private static $instance = false;

    private function __construct() {
        global $wpdb;

        $this->db_table = $wpdb->prefix . 'boomi_subprocessors_email';
        $this->stp_db = \BoomiCMS\BC_DB::getInstance()->subscribe_to_page();
    }

    public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
    }

    public function migrate_subscribe_data(int $post_id = 0) {
        if ( !$post_id ) {
            return;  
        }

        $migrated_row_ids = $this->migrate_email_db( array($post_id) ); // array - may be empty
        $remove_db        = $this->remove_email_db(); // 1|0
        $this->remove_email_options();
        
        return array(
            'migrated_row_ids' => $migrated_row_ids,
            'db_removed'        => $remove_db,
        );       
    }

    private function migrate_email_db( array $posts ) {
        global $wpdb;

        if (!$this->db_table_exists()) {
            return array();
        }
    
        $migrated_rows      = array();
        $db_result = $wpdb->get_results( "SELECT * FROM $this->db_table" );
    
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
    
    private function remove_email_db() {
        global $wpdb;
    
        $deleted = $wpdb->query( "DROP TABLE IF EXISTS $this->db_table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    
        \delete_option( 'wp_boomi_subprocessors_email_db_version' );
    
        return $deleted;
    }  
    
    private function remove_email_options() {
        \delete_option( '_bc_subprocessors_subscribe_emails' );
        \delete_option( '_bc_subprocessors_subscribe_settings' );
    }

    private function db_table_exists() {
        global $wpdb;

        return $wpdb->get_var( "SHOW TABLES LIKE '{$this->db_table}'" );
    }

}