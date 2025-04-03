<?php
/**
 * Setups the base taxonomies functionality
 *
 * @package     erikdmitchell\bcmigration\Abstracts
 * @since     0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\abstracts;

/**
 * Taxonomies Class
 */
abstract class Taxonomies {

    public function __construct() {

    }

    public function get(string $name = '') {

    }

    public function get_by_post_type(string $post_type = '') {
        $taxonomies = get_object_taxonomies($post_type, 'names');
        
        return $taxonomies;       
    }

    public function rename() {}
    
    public function merge() {}
    
    public function delete() {}
}
