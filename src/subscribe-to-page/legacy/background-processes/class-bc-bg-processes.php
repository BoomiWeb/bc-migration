<?php
/**
 * Boomi background processes.
 *
 * See https://github.com/A5hleyRich/wp-background-processing-example/ for an example.
 *
 * @package BoomiCMS\BackgroundProcesses
 * @since   4.4.0
 * @version 0.2.0
 */

namespace BoomiCMS\BackgroundProcesses;

/**
 * BC_BG_Processes class.
 */
class BC_BG_Processes {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->includes();
    }

    /**
     * Include plugin files.
     *
     * @access public
     * @return void
     */
    public function includes() {
        include_once __DIR__ . '/class-bc-connectors-update.php';
        include_once __DIR__ . '/class-bc-shortcode-search.php';
    }
}

new BC_BG_Processes();
