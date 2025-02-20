<?php


namespace erikdmitchell\bcmigration;

use erikdmitchell\bcmigration\cli\CLI;

class BCMigration {

    private static $instance = false;

    private function __construct() {}

    public static function init() {
		if ( ! self::$instance ) {
			self::$instance = new self();

            if (defined('WP_CLI') && WP_CLI) {
                new CLI();
            }
		}

		return self::$instance;
    }

}