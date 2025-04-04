<?php
/**
 * Logger trait class
 *
 * @package erikdmitchell\bcmigration\traits
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\traits;

trait LoggerTrait {
    protected string $log_name = 'default.log'; // Default log file name
    protected string $log_dir = BCM_PATH; // Directory to store logs

    /**
     * Set a custom log name.
     */
    public function set_log_name(string $name): void {
        $this->log_name = $name;
    }

    /**
     * Write a message to the log.
     */
    public function log(string $message, ?string $log_name = null): void {
        $log_file = $this->get_log_file_path($log_name ?? $this->log_name);

        if (!is_dir($this->log_dir)) {
            mkdir($this->log_dir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[{$timestamp}] {$message}" . PHP_EOL;

        file_put_contents($log_file, $formatted, FILE_APPEND);
    }

    /**
     * Get the full path of the log file.
     */
    protected function get_log_file_path(string $log_name): string {
        return rtrim($this->log_dir, '/') . '/' . ltrim($log_name, '/');
    }
}