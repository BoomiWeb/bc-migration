<?php
/**
 * Map Post Data abstract class
 *
 * @package erikdmitchell\bcmigration\abstracts
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\abstracts;

/**
 * MapPostData class
 */
abstract class MapPostData {

	/**
	 * Caller object
	 *
	 * @var object
	 */
	protected $caller;

	/**
	 * Constructor for MapPostData class.
	 *
	 * @param object|null $caller Optional. The caller object that can be used
	 *                            to add notices or log messages. Default is null.
	 */
	public function __construct( $caller = null ) {
		$this->caller = $caller;
	}

	/**
	 * Log a message.
	 *
	 * If the caller object has a `log` method, it will be called with the given
	 * message and level. Otherwise, the message will be discarded.
	 *
	 * @param string $message The message to log.
	 * @param string $level   Optional. The log level. Defaults to 'info'.
	 */
	protected function log( string $message, string $level = 'info' ) {
		if ( $this->caller && method_exists( $this->caller, 'log' ) ) {
			$this->caller->log( $message, $level );
		}
	}

	/**
	 * Add a notice to the caller object.
	 *
	 * If the caller object has an `add_notice` method, it will be called with the given
	 * message and level. Otherwise, the message will be discarded.
	 *
	 * @param string $message The message to add as a notice.
	 * @param string $level   Optional. The type of notice. Defaults to 'info'.
	 */
	protected function add_notice( string $message, string $level = 'info' ) {
		if ( $this->caller && method_exists( $this->caller, 'add_notice' ) ) {
			$this->caller->add_notice( $message, $level );
		}
	}
}
