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

    /**
     * The Rivery API instance.
     *
     * @var API
     */
    public API $api;

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
        // Initialize the API.
        $this->init_api();

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

    private function init_api() {
        try {
            $this->api = API::init();
        } catch (\RuntimeException $e) {
            error_log('Rivery API credentials are missing: ' . $e->getMessage());

            return;
        }
    }

    private function init_admin_settings() {     
        $admin_settings = new AdminSettings();

        // Add AJAX handler for connection test.
        // FIXME: this does not belong here.
        add_action('wp_ajax_bcm_test_api_connection', array($admin_settings, 'test_connection'));

       return $admin_settings;
    }
}
