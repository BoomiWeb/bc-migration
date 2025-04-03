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

        <?php
$tax_class = BlogTaxonomies::init();

$blog_tax = $tax_class->taxonomies();

foreach ($blog_tax as $tax) {
    echo '<h2>' . $tax . '</h2>';
}
        ?>
    </div>
    <?php
}
