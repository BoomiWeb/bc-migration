<?php
/**
 * Rivery import integrations class
 *
 * @package erikdmitchell\bcmigration\rivery\import
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\rivery\import;

use erikdmitchell\bcmigration\rivery\Rivery;
use WP_Error;

/**
 * Integrations import class.
 */
class Integrations {

    /**
     * The single instance of the class.
     *
     * @var Integrations|null
     */
    protected static ?Integrations $instance = null;

    /**
     * Gets the single instance of the class.
     *
     * @return Integrations Single instance of the class.
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get_integrations() {
        $response = Rivery::init()->api->request('integrations', 'GET', array(
            'per_page' => 2, // TODO: check as this may not work
        ));

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'rivery_integration_error', 'Failed to fetch Rivery integrations: ' . $response->get_error_message() );
        }

        if ( empty( $response ) || ! is_array( $response ) ) {
            return new WP_Error( 'rivery_integration_error', 'No integrations found or invalid response format.' );
        }

        if ( ! isset( $response['body'] ) || empty( $response['body'] ) ) {
            return new WP_Error( 'rivery_integration_error', 'No integrations data found in the response.' );
        }

        $integrations = json_decode( $response['body'], true );

        if ( ! is_array( $integrations ) ) {
            return new WP_Error( 'rivery_integration_error', 'Invalid integrations data format.' );
        }

        if ( empty( $integrations ) ) {
            return new WP_Error( 'rivery_integration_error', 'No integrations to migrate.' );
        }

        return $integrations;
    }

    public function format_integrations( $integrations ) {
        $formatted = array();

        foreach ( $integrations as $integration ) {
            $formatted[] = $this->format_integration_for_import( $integration );
        }

        return $formatted;
    }

    public function get_icon(int $icon_id) {
        $response = Rivery::init()->api->request('media/' . $icon_id);

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'rivery_icon_error', 'Failed to fetch Rivery icon: ' . $response->get_error_message() );
        }

        if ( empty( $response['body'] ) ) {
            return new WP_Error( 'rivery_icon_error', 'No icon data found in the response.' );
        }

        $body = json_decode( $response['body'], true );

        return $body['link'] ?? '';
    }

    private function format_integration_for_import( $integration ) {      
        return array(
            'post_id'          => $integration['id'],
            'name'        => $integration['title']['rendered'] ?? '',
            'icon_id'    => $integration['featured_media'] ?? 0,
            'icon_url' => $this->get_icon($integration['featured_media']) ?? '',
            'slug'        => $integration['slug'] ?? '',
            'description' => $integration['content']['rendered'] ?? '',
            'parent_id' => $integration['parent'] ?? 0,
        );
    }

}