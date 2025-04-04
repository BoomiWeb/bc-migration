<?php

namespace erikdmitchell\bcmigration;

use erikdmitchell\bcmigration\abstracts\Taxonomies;

class BlogTaxonomies extends Taxonomies {

    public $taxonomies;

    /**
     * The single instance of the class.
     *
     * @var BlogTaxonomies|null
     */
    protected static $instance = null;

    public function __construct() {
        $this->taxonomies = $this->get_by_post_type('blog');

        parent::__construct();
    }

    /**
     * Gets the single instance of the class.
     *
     * @return BlogTaxonomies Single instance of the class.
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function taxonomies() {
        return $this->taxonomies;
    }

    // public function get_taxonomy_tags(string $tag = '') {
    //     return $this->get($tag);
    // }

}