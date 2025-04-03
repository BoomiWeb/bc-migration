<?php

namespace erikdmitchell\bcmigration;

// Prevent direct access to the file.
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add the admin page under Tools
function code_testing_tool_add_admin_page() {
    add_submenu_page(
        'tools.php', // Parent slug (Tools menu)
        'Code Tester', // Page title
        'Code Tester', // Menu title
        'manage_options', // Capability required to access
        'code-tester', // Menu slug
        __NAMESPACE__ . '\code_testing_tool_page_content', // Callback function to display content
    );
}
add_action('admin_menu', __NAMESPACE__ . '\code_testing_tool_add_admin_page');

// Content of the admin page
function code_testing_tool_page_content() {
    ?>
    <div class="wrap">
        <h1>Code Tester</h1>
        <p>This is a simple page to test code snippets.</p>

        <form method="post">
            <label for="code-input">Enter Code:</label><br>
            <textarea id="code-input" name="code-input" rows="10" cols="50"></textarea><br>
            <input type="submit" name="submit-code" value="Run Code">
        </form>

        <?php
        // Handle code submission
        if (isset($_POST['submit-code'])) {
            $code = isset($_POST['code-input']) ? $_POST['code-input'] : '';
            if (!empty($code)) {
                echo "<h2>Code Output:</h2>";
                // Execute the code (be careful with this!)
                ob_start(); // Start output buffering
                eval($code); // Execute the code
                $output = ob_get_clean(); // Get the output
                echo "<pre>" . htmlspecialchars($output) . "</pre>"; // Display the output
            }
        }
        ?>
    </div>
    <?php
}
