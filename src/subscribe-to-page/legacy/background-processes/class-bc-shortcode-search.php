<?php
/**
 * Shortcode search background process.
 *
 * @package BoomiCMS\BackgroundProcesses
 * @since   4.4.0
 * @version 0.1.0
 */

namespace BoomiCMS\BackgroundProcesses;

/**
 * BC_Shortcode_Search class.
 */
class BC_Shortcode_Search {

    /**
     * Shortcode search process.
     *
     * @var BC_Shortcode_Search_Process
     */
    protected $process_all;

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'process_shortcode_search' ) );
    }

    /**
     * Init
     *
     * @return void
     */
    public function init() {
        require_once plugin_dir_path( __FILE__ ) . 'bg-processes/class-bc-shortcode-search-process.php';

        $this->process_all = new BC_Shortcode_Search_Process();
    }

    /**
     * Process the shortcode search.
     *
     * @return mixed Will return nothing if the request is not valid.
     */
    public function process_shortcode_search() {
        if ( ! isset( $_GET['bc_sc_search_process'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'bc_bg_process' ) ) {
            return;
        }

        if ( ! isset( $_GET['shortcode'] ) ) {
            return;
        }

        if ( 'run' === $_GET['bc_sc_search_process'] ) {
            $this->handle_shortcode_search( sanitize_text_field( wp_unslash( $_GET['shortcode'] ) ) );
        }
    }

    /**
     * Adds a post type to the queue for processing.
     *
     * @param string $shortcode The shortcode to search for.
     * @param array  $post_types An array of post types to search in. Defaults to all public post types except attachments.
     * @return void
     */
    protected function handle_shortcode_search( $shortcode = '', $post_types = array() ) {
        if ( empty( $shortcode ) ) {
            return;
        }

        if ( empty( $post_types ) ) {
            $post_types = get_post_types( array( 'public' => true ), 'names' );
            unset( $post_types['attachment'] ); // we don't want to search for attachments.
        }

        foreach ( $post_types as $post_type ) {
            $this->process_all->push_to_queue(
                array(
                    'post_type' => $post_type,
                    'shortcode' => $shortcode,
                )
            );
        }

        $this->process_all->save()->dispatch();
    }
}

new BC_Shortcode_Search();
