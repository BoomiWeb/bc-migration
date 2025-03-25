<?php
/**
 * BC Migration functions
 *
 * @package erikdmitchell\bcmigration
 * @since   0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

/**
 * Convert a camel case string to snake case.
 *
 * @param string $input The camel case string.
 * @return string A string in snake case.
 */
function camel_to_snake( $input = '' ) {
    return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $input ) );
}