<?php
/**
 * Rivery Admin Settings class
 *
 * @package erikdmitchell\bcmigration\rivery
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\rivery;

class AdminSettings {

    private $option_group = 'bcm_rivery_settings';
    private $option_name = 'bcm_rivery_credentials';
    private $menu_slug = 'bcm-rivery-settings';

    public function __construct() {        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            'Rivery Settings',
            'Rivery Settings',
            'manage_options',
            $this->menu_slug,
            array($this, 'settings_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function settings_init() {
        register_setting($this->option_group, $this->option_name, array(
            'sanitize_callback' => array($this, 'sanitize_credentials'),
            'default' => $this->get_default_options()
        ));
        
        add_settings_section(
            'wp_api_credentials_section',
            'API Credentials',
            array($this, 'credentials_section_callback'),
            $this->option_group
        );
        
        add_settings_field(
            'api_url',
            'API URL',
            array($this, 'api_url_field'),
            $this->option_group,
            'wp_api_credentials_section'
        );
        
        add_settings_field(
            'username',
            'Username',
            array($this, 'username_field'),
            $this->option_group,
            'wp_api_credentials_section'
        );
        
        add_settings_field(
            'password',
            'Application Password',
            array($this, 'password_field'),
            $this->option_group,
            'wp_api_credentials_section'
        );
        
        add_settings_field(
            'test_connection',
            'Test Connection',
            array($this, 'test_connection_field'),
            $this->option_group,
            'wp_api_credentials_section'
        );
    }
    
    /**
     * Get default options
     */
    private function get_default_options() {
        return array(
            'api_url' => '',
            'username' => '',
            'password' => '',
            'encrypted' => false
        );
    }
    
    /**
     * Sanitize credentials before saving
     */
    public function sanitize_credentials($input) {
        $sanitized = array();
        
        $sanitized['api_url'] = esc_url_raw($input['api_url']);
        $sanitized['username'] = sanitize_text_field($input['username']);
        
        // Encrypt password before storing
        if (!empty($input['password'])) {
            $sanitized['password'] = $this->encrypt_password($input['password']);
            $sanitized['encrypted'] = true;
        } else {
            // Keep existing password if field is empty
            $existing = get_option($this->option_name, $this->get_default_options());
            $sanitized['password'] = $existing['password'];
            $sanitized['encrypted'] = $existing['encrypted'];
        }
        
        return $sanitized;
    }
    
    /**
     * Encrypt password for storage
     */
    private function encrypt_password($password) {
        if (!defined('AUTH_KEY') || !defined('AUTH_SALT')) {
            return base64_encode($password); // Fallback to base64
        }
        
        $key = hash('sha256', AUTH_KEY . AUTH_SALT);
        $iv = substr(hash('sha256', AUTH_SALT), 0, 16);
        
        return base64_encode(openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv));
    }
    
    /**
     * Decrypt password for use
     */
    private function decrypt_password($encrypted_password) {
        if (!defined('AUTH_KEY') || !defined('AUTH_SALT')) {
            return base64_decode($encrypted_password); // Fallback from base64
        }
        
        $key = hash('sha256', AUTH_KEY . AUTH_SALT);
        $iv = substr(hash('sha256', AUTH_SALT), 0, 16);
        
        return openssl_decrypt(base64_decode($encrypted_password), 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Section callback
     */
    public function credentials_section_callback() {
        echo '<p>Enter your API credentials to connect to the external WordPress site.</p>';
    }
    
    /**
     * API URL field
     */
    public function api_url_field() {
        $options = get_option($this->option_name, $this->get_default_options());
        ?>
        <input type="url" 
               name="<?php echo $this->option_name; ?>[api_url]" 
               value="<?php echo esc_attr($options['api_url']); ?>" 
               class="regular-text" 
               placeholder="https://example.com/wp-json/wp/v2/" />
        <p class="description">Full URL to the WordPress REST API endpoint</p>
        <?php
    }
    
    /**
     * Username field
     */
    public function username_field() {
        $options = get_option($this->option_name, $this->get_default_options());
        ?>
        <input type="text" 
               name="<?php echo $this->option_name; ?>[username]" 
               value="<?php echo esc_attr($options['username']); ?>" 
               class="regular-text" />
        <p class="description">WordPress username for API access</p>
        <?php
    }
    
    /**
     * Password field
     */
    public function password_field() {
        ?>
        <input type="password" 
               name="<?php echo $this->option_name; ?>[password]" 
               value="" 
               class="regular-text" 
               placeholder="Enter new password to update" />
        <p class="description">WordPress Application Password (leave blank to keep existing)</p>
        <?php
    }
    
    /**
     * Test connection field
     */
    public function test_connection_field() {
        ?>
        <button type="button" id="test-api-connection" class="button button-secondary">Test Connection</button>
        <div id="connection-result" style="margin-top: 10px;"></div>
        <?php
    }
    
    /**
     * Settings page HTML
     */
    public function settings_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('wp_api_settings', 'settings_updated', 'Settings saved successfully!', 'updated');
        }
        ?>
        <div class="wrap">
            <h1>API Settings</h1>
            <?php settings_errors('wp_api_settings'); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_group);
                do_settings_sections($this->option_group);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_' . $this->menu_slug) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_admin_js());
    }
    
    /**
     * Get admin JavaScript
     */
    private function get_admin_js() {
        return "
        jQuery(document).ready(function($) {
            $('#test-api-connection').click(function() {
                var button = $(this);
                var result = $('#connection-result');
                
                button.prop('disabled', true).text('Testing...');
                result.html('<span style=\"color: #666;\">Testing connection...</span>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bcm_test_api_connection',
                        nonce: '" . wp_create_nonce('test_api_connection') . "'
                    },
                    success: function(response) {
                        if (response.success) {
                            result.html('<span style=\"color: green;\">✓ Connection successful!</span>');
                        } else {
                            result.html('<span style=\"color: red;\">✗ Connection failed: ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        result.html('<span style=\"color: red;\">✗ Connection test failed</span>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Connection');
                    }
                });
            });
        });
        ";
    }
    
    /**
     * Get stored credentials
     */
    public function get_credentials() {
        $options = get_option($this->option_name, $this->get_default_options());
        
        if (!empty($options['password']) && $options['encrypted']) {
            $options['password'] = $this->decrypt_password($options['password']);
        }
        
        return $options;
    }
    
    /**
     * Check if credentials are configured
     */
    public function has_credentials() {
        $creds = $this->get_credentials();
        return !empty($creds['api_url']) && !empty($creds['username']) && !empty($creds['password']);
    }
    
    // FIXME: This is in the API class, not here.
    /**
     * Make authenticated API request
     * 
     */
    public function api_request($endpoint, $method = 'GET', $data = array()) {
        if (!$this->has_credentials()) {
            return new WP_Error('no_credentials', 'API credentials not configured');
        }
        
        $creds = $this->get_credentials();
        $url = trailingslashit($creds['api_url']) . ltrim($endpoint, '/');
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($creds['username'] . ':' . $creds['password']),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = wp_json_encode($data);
        }
        
        return wp_remote_request($url, $args);  
    }
    
    /**
     * Test API connection (AJAX handler)
     */
    public function test_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'test_api_connection')) {
            wp_die('Security check failed');
        }
        
        if (!$this->has_credentials()) {
            wp_send_json_error('No credentials configured');
        }
        
        $response = $this->api_request('');
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            wp_send_json_success('Connection successful');
        } else {
            wp_send_json_error('HTTP ' . $code . ': ' . wp_remote_retrieve_response_message($response));
        }
    }
}

