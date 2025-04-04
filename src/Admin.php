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

// $blog_tax = $tax_class->taxonomies();

// foreach ($blog_tax as $tax) {
//     echo '<h2>' . $tax . '</h2>';
// }
// echo '<pre>';
// print_r($tax_class->get_terms('industries'));
// echo '</pre>';
        ?>
    </div>
    <?php
echo '<h2>Renaming</h2>';
echo 'check error log';
$terms_to_rename = [
    [
        'taxonomy' => 'industries',
        'old'      => 'TT',
        'new'      => 'Testing',
    ],
    [
        'taxonomy' => 'products',
        'old'      => 'FB',
        'new'      => 'Foo Bar',
    ],
];

// foreach ( $terms_to_rename as $change ) {
//     $result = $tax_class->rename( $change['taxonomy'], $change['old'], $change['new'] );

//     if ( is_wp_error( $result ) ) {
//         error_log( "Failed to rename '{$change['old']}' in '{$change['taxonomy']}': " . $result->get_error_message() );
//     } else {
//         error_log( "Renamed '{$change['old']}' to '{$change['new']}' in '{$change['taxonomy']}'" );
//     }
// }

}



