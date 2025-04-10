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
        $this->upload_dir = trailingslashit(BCM_UPLOADS_PATH);
        $this->upload_url = trailingslashit(BCM_UPLOADS_URL);        
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

    public function delete() {      
        if (isset($_POST['bcm_delete_file']) && current_user_can('manage_options')) {
            check_admin_referer('bcm_delete_file_action');

            $delete_file = basename($_POST['bcm_delete_file']); // sanitize.
            $file_path = $this->upload_dir . $delete_file;
            
            if (file_exists($file_path)) {
                unlink($file_path);
                echo '<div class="notice notice-success"><p>Deleted: ' . esc_html($delete_file) . '</p></div>';
            }
        }        
    }
}