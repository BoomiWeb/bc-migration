<?php
/**
 * Rivery class
 *
 * @package erikdmitchell\bcmigration\rivery
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\rivery;

class Rivery {

    /**
     * The single instance of the class.
     *
     * @var Rivery|null
     */
    protected static ?Rivery $instance = null;

    /**
     * Initializes the class and sets the database properties.
     *
     * @internal
     */
    private function __construct() {}

    /**
     * Gets the single instance of the class.
     *
     * @return Rivery Single instance of the class.
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
