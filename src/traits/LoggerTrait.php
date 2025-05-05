<?php
/**
 * Logger trait class
 *
 * @package erikdmitchell\bcmigration\traits
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\traits;

/**
 * LoggerTrait class
 */
trait LoggerTrait {
    /**
     * The name of the log file to write to.
     *
     * @var string
     */
    protected string $log_name = 'default.log';

    /**
     * The directory where the log file will be created.
     *
     * @var string
     */
    protected string $log_dir = BCM_UPLOADS_PATH;

    /**
     * Sets the name of the log file to write to.
     *
     * @param string $name The name of the log file.
     *
     * @return void
     */
    public function set_log_name( string $name ): void {
        $this->log_name = $name;
    }

    /**
     * Logs a message to a specified log file, creating the log directory if it does not exist.
     *
     * @param string      $message  The message to log.
     * @param string      $type     Optional. The type of log message. Default is 'info'.
     * @param string|null $log_name Optional. The name of the log file to write to.
     *                              If null, the default log file name is used.
     *
     * @return void
     */
    public function log( string $message, string $type = 'info', ?string $log_name = null ): void {
        $log_file = $this->get_log_file_path( $log_name ?? $this->log_name );
        $type = strtoupper( $type );

        if ( ! is_dir( $this->log_dir ) ) {
            mkdir( $this->log_dir, 0775, true );
        }

        $timestamp = gmdate( 'Y-m-d H:i:s' );
        $formatted = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;

        file_put_contents( $log_file, $formatted, FILE_APPEND );
    }

    /**
     * Gets the full path to the log file.
     *
     * @param string $log_name The log file name.
     * @return string The full path to the log file.
     */
    protected function get_log_file_path( string $log_name ): string {
        return rtrim( $this->log_dir, '/' ) . '/' . ltrim( $log_name, '/' );
    }
}
