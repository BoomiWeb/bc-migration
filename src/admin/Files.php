<?php
/**
 * Process files for the plugin.
 *
 * @package erikdmitchell\bcmigration\admin
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\admin;

class Files {

	protected string $upload_dir;
	protected string $upload_url;

	/**
	 * The single instance of the class.
	 *
	 * @var Files|null
	 */
	protected static ?Files $instance = null;

	private function __construct() {
		$this->upload_dir = trailingslashit( BCM_UPLOADS_PATH );
		$this->upload_url = trailingslashit( BCM_UPLOADS_URL );
	}

	/**
	 * Gets the single instance of the class.
	 *
	 * @return Files Single instance of the class.
	 */
	public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function upload() {
		if ( isset( $_POST['bcm_upload_csv'] ) && check_admin_referer( 'bcm_upload_csv_action' ) ) {
			if ( ! empty( $_FILES['bcm_csv_file']['tmp_name'] ) ) {
				$result = $this->handle_csv_upload( $_FILES['bcm_csv_file'] );

				if ( is_wp_error( $result ) ) {
					echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
				} else {
					echo '<div class="notice notice-success"><p>Uploaded: ' . esc_html( basename( $result ) ) . '</p></div>';
				}
			}
		}
	}

	public function delete() {
		if ( isset( $_POST['bcm_delete_file'] ) && current_user_can( 'manage_options' ) ) {
			check_admin_referer( 'bcm_delete_file_action' );

			$delete_file = basename( $_POST['bcm_delete_file'] ); // sanitize.
			$file_path   = $this->upload_dir . $delete_file;

			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
				echo '<div class="notice notice-success"><p>Deleted: ' . esc_html( $delete_file ) . '</p></div>';
			}
		}
	}

	private function handle_csv_upload( $file ) {
		if ( ! is_uploaded_file( $file['tmp_name'] ) || $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'upload_error', 'File upload failed or was invalid.' );
		}

		$extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( $extension !== 'csv' ) {
			return new WP_Error( 'invalid_type', 'Only CSV files are allowed.' );
		}

		$mime         = mime_content_type( $file['tmp_name'] );
		$allowed_mime = array( 'text/plain', 'text/csv', 'application/vnd.ms-excel' );
		if ( ! in_array( $mime, $allowed_mime, true ) ) {
			return new WP_Error( 'invalid_mime', 'Uploaded file is not a valid CSV.' );
		}

		$filename    = sanitize_file_name( $file['name'] );
		$destination = trailingslashit( $this->upload_dir ) . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
			return new WP_Error( 'move_failed', 'Failed to move uploaded file.' );
		}

		return $destination;
	}
}
