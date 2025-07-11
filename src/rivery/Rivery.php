<?php
/**
 * Rivery class
 *
 * @package erikdmitchell\bcmigration\rivery
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\rivery;

class Rivery {

    private $admin_settings;

    /**
     * The single instance of the class.
     *
     * @var Rivery|null
     */
    protected static ?Rivery $instance = null;

    /**
     * Initializes the class and sets the database properties.
     *
     * @internal
     */
    private function __construct() {
        if (is_admin()) {
            $this->init_admin_settings();
        }
    }

    /**
     * Gets the single instance of the class.
     *
     * @return Rivery Single instance of the class.
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function init_admin_settings() {     
        $admin_settings = new AdminSettings();

        // Add AJAX handler for connection test.
        // TODO: is this needed?
        add_action('wp_ajax_bcm_test_api_connection', array($admin_settings, 'test_connection'));

       return $admin_settings;
    }
}
