<?php
/**
 * Main admin class for the plugin.
 *
 * @package erikdmitchell\bcmigration\admin
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\admin;

/**
 * Admin class
 */
class Admin {

	/**
	 * Set up the admin page.
	 *
	 * Hooks into the admin_init and admin_menu actions.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'check_file_actions' ), 0 );
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Check for file actions.
	 *
	 * Hooks into the admin_init action and calls Files::delete() and Files::upload() to handle file actions.
	 *
	 * @return void
	 */
	public function check_file_actions() {
		Files::init()->delete();
		Files::init()->upload();
	}

	/**
	 * Register the admin menu item.
	 *
	 * Hooks into the admin_menu action and registers a new menu item under the Tools menu.
	 *
	 * @return void
	 */
	public function menu() {
		add_submenu_page(
			'tools.php',
			'Taxonomy Migration',
			'Taxonomy Migration',
			'manage_options',
			'taxonomy-migration',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Renders the admin page.
	 *
	 * Checks if the admin page file exists and includes it if it does. Otherwise, displays a notice.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		$path = BCM_PATH . '/admin/pages/admin.php';

		if ( file_exists( $path ) ) {
			include $path;
		} else {
			echo '<div class="notice notice-error"><p>Admin page not found.</p></div>';
		}
	}
}
