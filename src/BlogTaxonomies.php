<?php
/**
 * Blog Taxonomies class
 *
 * @package erikdmitchell\bcmigration
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

use erikdmitchell\bcmigration\abstracts\Taxonomies;

/**
 * BlogTaxonomies class
 */
class BlogTaxonomies extends Taxonomies {

    /**
     * The taxonomies associated with the 'blog' post type.
     *
     * @var array
     */
    public $taxonomies;

    /**
     * The single instance of the class.
     *
     * @var BlogTaxonomies|null
     */
    protected static $instance = null;

    /**
     * Constructor.
     *
     * Initializes the BlogTaxonomies class by fetching taxonomies related
     * to the 'blog' post type and calling the parent constructor.
     */
    public function __construct() {
        $this->taxonomies = $this->get_by_post_type( 'blog' );

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

    /**
     * Get the taxonomies associated with the 'blog' post type.
     *
     * @return array The taxonomies associated with the 'blog' post type.
     */
    public function taxonomies() {
        return $this->taxonomies;
    }
}
