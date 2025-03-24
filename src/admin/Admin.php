<?php
/**
 * Admin class
 *
 * @package BCMigration
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

class Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_tools_page' ) );
    }

    /**
     * Adds a Tools page to the WordPress admin menu.
     *
     * @return void
     */
    public function add_tools_page() {
        add_management_page(
            'BC Migration Tools', // Page title
            'BC Migration',      // Menu title
            'manage_options',    // Capability
            'bc-migration-tools', // Menu slug
            array( $this, 'render_tools_page' ) // Callback function to render the page
        );
    }

    /**
     * Renders the content of the Tools page.
     *
     * @return void
     */
    public function render_tools_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Load the page content from a separate file
        $page_content_path = __DIR__ . '/pages/admin.php';

        if ( file_exists( $page_content_path ) ) {
            include $page_content_path;
        } else {
            echo '<div class="notice notice-error"><p>Error: BC Migration Tools page content not found.</p></div>';
        }
    }
}
