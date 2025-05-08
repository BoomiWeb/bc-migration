<?php
/**
 * Map Post Data Interface class
 *
 * @package erikdmitchell\bcmigration\interfaces
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\interfaces;

interface MapPostDataInterface {

    public static function init();

    public static function map( int $post_id, array $map );
}