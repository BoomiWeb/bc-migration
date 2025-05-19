<?php
/**
 * Process files for the plugin.
 *
 * @package erikdmitchell\bcmigration\admin
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\admin;

/**
 * Files class
 */
class Files {

	/**
	 * The upload directory.
	 *
	 * @var string
	 */
	protected string $upload_dir;

	/**
	 * The upload directory URL.
	 *
	 * @var string
	 */
	protected string $upload_url;

	/**
	 * The single instance of the class.
	 *
	 * @var Files|null
	 */
	protected static ?Files $instance = null;

	/**
	 * Constructor.
	 *
	 * Initializes the upload directory and URL.
	 *
	 * @access private
	 */
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

	/**
	 * Uploads a CSV file.
	 *
	 * Handles the upload of a CSV file through the admin page.
	 *
	 * @return void
	 */
	public function upload() {
		if ( isset( $_POST['bcm_upload_csv'] ) && check_admin_referer( 'bcm_upload_csv_action' ) ) {
			if ( isset( $_FILES['bcm_csv_file'] ) && is_array( $_FILES['bcm_csv_file'] ) ) {
				$file = $_FILES['bcm_csv_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( isset( $file['tmp_name'] ) && ! empty( $file['tmp_name'] ) ) {
					$result = $this->handle_csv_upload( $_FILES['bcm_csv_file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

					if ( is_wp_error( $result ) ) {
						echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
					} else {
						echo '<div class="notice notice-success"><p>Uploaded: ' . esc_html( basename( $result ) ) . '</p></div>';
					}
				}
			}
		}
	}

	/**
	 * Handles the deletion of a CSV file.
	 *
	 * Checks if the file deletion request is valid and if the user has the capabilities to delete the file.
	 * If the file exists, it will be deleted and a success notice will be displayed.
	 *
	 * @return void
	 */
	public function delete() {
		if ( isset( $_POST['bcm_delete_file'] ) && current_user_can( 'manage_options' ) ) {
			check_admin_referer( 'bcm_delete_file_action' );

			$delete_file = basename( sanitize_text_field( wp_unslash( $_POST['bcm_delete_file'] ) ) );
			$file_path   = $this->upload_dir . $delete_file;

			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
				echo '<div class="notice notice-success"><p>Deleted: ' . esc_html( $delete_file ) . '</p></div>';
			}
		}
	}

	/**
	 * Handles the upload of a CSV file.
	 *
	 * Validates the uploaded file for correct type and mime type,
	 * and moves it to the upload directory if valid.
	 *
	 * @param array $file The uploaded file array from $_FILES.
	 *
	 * @return string|WP_Error The path to the uploaded file on success, or a WP_Error object on failure.
	 */
	private function handle_csv_upload( $file ) {
		if ( ! is_uploaded_file( $file['tmp_name'] ) || UPLOAD_ERR_OK !== $file['error'] ) {
			return new WP_Error( 'upload_error', 'File upload failed or was invalid.' );
		}

		$extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $extension ) {
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
