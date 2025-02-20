<?php
/**
 * Test class
 *
 * @package BCMigration
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

class Admin {

    public function __construct() {
        add_filter( 'boomi_cms_admin_menu_pages', array( $this, 'admin_menu_pages' ), 10, 2 );
        add_filter( 'boomi_cms_admin_get_page', array( $this, 'admin_get_page' ), 10, 3 );
    }
    
    /**
     * Add page to the admin menu.
     *
     * @param array  $hook_names An array of hook names.
     * @param string $main_slug The main slug for the admin menu.
     * @return array
     */
    public function admin_menu_pages( $hook_names, $main_slug ) {
        $hook_names[] = add_submenu_page( $main_slug, 'Dev Tools', 'Dev Tools', 'manage_options', 'dev-tools', array( 'BoomiCMS\\Admin\\BC_Admin', 'load_view_static' ), 999 );

        return $hook_names;
    }

    /**
     * Load the admin page.
     *
     * @param string $path The full page path.
     * @param string $raw_path The raw slug.
     * @param array  $args Any passed args.
     * @return string The page path.
     */
    public function admin_get_page( $path, $raw_path, $args ) {       
        if ( 'boomi-cms_page_dev-tools' === $raw_path ) {
            return __DIR__ . '/pages/admin.php';
        }

        return $path;
    }

}
