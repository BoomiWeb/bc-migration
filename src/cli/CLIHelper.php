<?php
/**
 * CLI Helper
 *
 * @package erikdmitchell\bcmigration\cli
 * @since 0.1.0
 * @version 0.2.0
 */

namespace erikdmitchell\bcmigration\cli;

use WP_CLI;

class CLIHelper {
    /**
     * Outputs a message to the CLI.
     *
     * @param string $message The message to display.
     * @param string $type The type of message. Can be 'info', 'success', 'warning', 'error'.
     *                     Defaults to 'info'.
     * @return void
     */
    public static function output(string $message = '', string $type = 'info') {
        if (empty($message)) {
            return;
        }

        if (!in_array($type, ['info', 'success', 'warning', 'error'])) {
            $type = 'info';
        }

        switch ($type) {
            case 'info':
                WP_CLI::log($message);
                break;
            case 'success':
                WP_CLI::success($message);
                break;
            case 'warning':
                WP_CLI::warning($message);
                break;
            case 'error':
                WP_CLI::error($message);
                break;
        }
    }
}