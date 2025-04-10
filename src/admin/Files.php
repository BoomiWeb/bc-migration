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
                $uploaded_file = $_FILES['bcm_csv_file'];

                if ( strtolower( pathinfo( $uploaded_file['name'], PATHINFO_EXTENSION ) ) === 'csv' ) {
                    $filename    = sanitize_file_name( $uploaded_file['name'] );
                    $destination = $this->upload_dir . $filename;

                    if ( move_uploaded_file( $uploaded_file['tmp_name'], $destination ) ) {
                        echo '<div class="notice notice-success"><p>Uploaded: ' . esc_html( $filename ) . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>Failed to upload file.</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-error"><p>Only CSV files are allowed.</p></div>';
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
                unlink( $file_path );
                echo '<div class="notice notice-success"><p>Deleted: ' . esc_html( $delete_file ) . '</p></div>';
            }
        }
    }
}
