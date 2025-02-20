<?php
/**
 * Shortcode search background process.
 *
 * @package erikdmitchell\bcmigration\BackgroundProcesses
 * @since 0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

/**
 * ShortcodeSearch class.
 */
class ShortcodeSearch {
    /**
     * Instance of the class.
     *
     * @var ShortcodeSearch|null
     */
    private static $instance = null;

    /**
     * Shortcode search process.
     *
     * @var ShortcodSearchProcess
     */
    protected $process_all;

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * Get the singleton instance.
     *
     * @return ShortcodeSearch
     */
    public static function get_instance(): ShortcodeSearch {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserializing.
     */
    private function __wakeup() {}

    /**
     * Init
     *
     * @return void
     */
    public function init() {
        require_once __DIR__ . '/bg-processes/ShortcodSearchProcess.php';
        $this->process_all = new ShortcodSearchProcess();
    }

    /**
     * Process shortcode search.
     *
     * @param string $shortcode
     * @return void
     */
    public function process_shortcode_search( string $shortcode = '' ) {
        if ( ! $shortcode ) {
            return;
        }

        $this->handle_shortcode_search( $shortcode );
    }

    /**
     * Handle shortcode search.
     *
     * @param string $shortcode
     * @param array $post_types
     * @return void
     */
    protected function handle_shortcode_search( string $shortcode = '', array $post_types = array() ) {
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
