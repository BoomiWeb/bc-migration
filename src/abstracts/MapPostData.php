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

    public static function init() {}

    public static function map( int $post_id, array $map ) {}
}