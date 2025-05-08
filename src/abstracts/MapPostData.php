<?php
/**
 * Map Post Data abstract class
 *
 * @package erikdmitchell\bcmigration\abstracts
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\abstracts;

abstract class MapPostData {

	protected $caller;

	public function __construct( $caller = null ) {
		$this->caller = $caller;
	}

	protected function log( string $message, string $level = 'info' ) {       
		if ( $this->caller && method_exists( $this->caller, 'log' ) ) {           
			$this->caller->log( $message, $level );
		}
	}

	protected function add_notice( string $message, string $level = 'info' ) {
		if ( $this->caller && method_exists( $this->caller, 'add_notice' ) ) {
			$this->caller->add_notice( $message, $level );
		}
	}
}