<!-- function bcm_render_taxonomy_migration_page() { -->
<?php
    $upload_dir = trailingslashit(BCM_UPLOADS_PATH);
    $upload_url = trailingslashit(BCM_UPLOADS_URL);



    // Handle upload
    if (isset($_POST['bcm_upload_csv']) && check_admin_referer('bcm_upload_csv_action')) {
        if (!empty($_FILES['bcm_csv_file']['tmp_name'])) {
            $uploaded_file = $_FILES['bcm_csv_file'];
            if (strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION)) === 'csv') {
                $filename = sanitize_file_name($uploaded_file['name']);
                $destination = $upload_dir . $filename;
                if (move_uploaded_file($uploaded_file['tmp_name'], $destination)) {
                    echo '<div class="notice notice-success"><p>Uploaded: ' . esc_html($filename) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Failed to upload file.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Only CSV files are allowed.</p></div>';
            }
        }
    }

    // Get files
    $files = glob($upload_dir . '*.{csv,log}', GLOB_BRACE);
    ?>

    <div class="wrap">
        <h1>Taxonomy Migration</h1>

        <form method="post" enctype="multipart/form-data" style="margin-bottom:20px;">
            <?php wp_nonce_field('bcm_upload_csv_action'); ?>
            <input type="file" name="bcm_csv_file" accept=".csv" required />
            <input type="submit" name="bcm_upload_csv" class="button button-primary" value="Upload CSV" />
        </form>

        <h2>Uploaded Files</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Upload Date</th>
                    <th>Size</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($files)) {
                    foreach ($files as $file_path) {
                        $filename = basename($file_path);
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $file_url = $upload_url . $filename;
                        $file_size = size_format(filesize($file_path), 2);
                        $upload_time = date("Y-m-d H:i:s", filemtime($file_path));
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($filename); ?></strong></td>
                            <td><?php echo esc_html($upload_time); ?></td>
                            <td><?php echo esc_html($file_size); ?></td>
                            <td>
                                <a href="<?php echo esc_url($file_url); ?>" class="button">Download</a>
                                <?php if ($ext === 'log'): ?>
                                    <button type="button" class="button button-secondary" onclick="toggleLog('<?php echo esc_attr($filename); ?>')">View</button>
                                <?php endif; ?>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('bcm_delete_file_action'); ?>
                                    <input type="hidden" name="bcm_delete_file" value="<?php echo esc_attr($filename); ?>" />
                                    <button type="submit" class="button button-link-delete" onclick="return confirm('Delete <?php echo esc_js($filename); ?>?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php if ($ext === 'log'): ?>
                            <tr id="log-<?php echo esc_attr($filename); ?>" style="display:none;">
                                <td colspan="4">
                                    <textarea readonly rows="10" style="width:100%;"><?php echo esc_textarea(file_get_contents($file_path)); ?></textarea>
                                </td>
                            </tr>
                        <?php endif;
                    }
                } else {
                    echo '<tr><td colspan="4">No files found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
    function toggleLog(filename) {
        const row = document.getElementById('log-' + filename);
        if (row.style.display === 'none') {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
    </script>
