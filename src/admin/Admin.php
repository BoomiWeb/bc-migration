<?php
/**
 * Main admin class for the plugin.
 *
 * @package erikdmitchell\bcmigration\admin
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\admin;

class Admin {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'check_file_actions' ), 0 );
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	public function check_file_actions() {
		Files::init()->delete();
		Files::init()->upload();
	}

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

	public function render_admin_page() {
		$path = BCM_PATH . '/admin/pages/admin.php';

		if ( file_exists( $path ) ) {
			include $path;
		} else {
			echo '<div class="notice notice-error"><p>Admin page not found.</p></div>';
		}
	}
}
