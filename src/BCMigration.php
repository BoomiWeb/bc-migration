<?php
/**
 * BC Migration class
 *
 * @package erikdmitchell\bcmigration
 * @since   0.1.0
 */

namespace erikdmitchell\bcmigration;

use erikdmitchell\bcmigration\cli\CLI;

// Setup our uploads path and url.
$wp_uploads_dir     = wp_upload_dir();
$bcm_dirname        = 'bc-migration';
$wp_uploads_path    = isset( $wp_uploads_dir['basedir'] ) ? $wp_uploads_dir['basedir'] . '/' . $bcm_dirname : '';
$wp_uploads_url     = isset( $wp_uploads_dir['baseurl'] ) ? $wp_uploads_dir['baseurl'] . '/' . $bcm_dirname : '';
$example_files_path = __DIR__ . '/examples';

// Define constants for the plugin.
define( 'BCM_PATH', __DIR__ );
define( 'BCM_URL', __DIR__ );
define( 'BCM_DIRNAME', $bcm_dirname );
define( 'BCM_UPLOADS_PATH', $wp_uploads_path );
define( 'BCM_UPLOADS_URL', $wp_uploads_url );
define( 'BCM_EXAMPLE_FILES_PATH', $example_files_path );
define( 'BCM_VERSION', '0.3.3' );

/**
 * BC Migration class.
 */
class BCMigration {

	/**
	 * The version number.
	 *
	 * @var string
	 */
	public $version = '0.3.2';

	/**
	 * The single instance of the class.
	 *
	 * @var BCMigration|null
	 */
	protected static ?BCMigration $instance = null;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	private function __construct() {
		$this->includes();
		$this->maybe_create_uploads_folder();
	}

	/**
	 * Gets the single instance of the class.
	 *
	 * @return BCMigration Single instance of the class.
	 */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				new CLI();
			}
		}

		return self::$instance;
	}

	/**
	 * Includes the necessary files for plugin functionality.
	 *
	 * @return void
	 */
	private function includes() {
		include_once __DIR__ . '/functions.php';

		if ( is_admin() ) {
			include_once __DIR__ . '/admin/Admin.php';

			new \erikdmitchell\bcmigration\admin\Admin();
		}
	}

	/**
	 * Checks if the uploads folder exists and creates it if not.
	 *
	 * @return void
	 */
	public function maybe_create_uploads_folder() {
		if ( ! is_dir( BCM_UPLOADS_PATH ) ) {
			mkdir( BCM_UPLOADS_PATH );
		}
	}
}
