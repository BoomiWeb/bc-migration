<?php
/**
 * Rivery migrate integrations CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\rivery;

use erikdmitchell\bcmigration\rivery\import\Integrations;
use erikdmitchell\bcmigration\rivery\Rivery;
use WP_CLI;

/**
 * MigrateIntegrations CLI class.
 */
class MigrateIntegrations {

	/**
	 * Migrates Rivery integrations data.
	 *
	 * @param string[]             $args       CLI positional arguments.
	 * @param array<string, mixed> $assoc_args CLI associative arguments.
	 * @return void
	 */
	public function run( $args, $assoc_args ) {
        WP_CLI::log( 'Starting Rivery integrations migration...' );
        WP_CLI::log('This should run a bg process to migrate Rivery integrations.');

        $integrations = Integrations::init()->get_integrations();

        if ( is_wp_error( $integrations ) ) {
            WP_CLI::error( 'Failed to fetch Rivery integrations: ' . $integrations->get_error_message() );
            return;
        }

        if ( empty( $integrations ) ) {
            WP_CLI::success( 'No integrations found to migrate.' );
            return;
        }

        $formatted_integrations = Integrations::init()->format_integrations( $integrations );
// print_r($formatted_integrations);
// return;
        foreach ( $formatted_integrations as $integration ) {
            $post_id = wp_insert_post(
                array(
                    'post_title'   => $integration['name'],
                    'post_content' => $integration['description'] ?? '',
                    'post_type'    => 'connector',
                    'post_status'  => 'draft',
                )
            );

            if ( is_wp_error( $post_id ) ) {
                return $post_id;
            }

            $post = get_post( $post_id );
        
            WP_CLI::log( 'Migrating integration: ' . $integration['name'] . ' (ID: ' . $integration['post_id'] . ')' );

// FIXME: does not work
$connection_link = 'https://docs.rivery.io/docs/connection-'. str_replace('_', '-', $integration['slug']);

// TODO: load icon into media library - SEE connector import in CMS plugin
            $attachment_id = \BoomiCMS\Connectors\Import\ConnectorIcons::get_instance()->update_post_icon(
                $post_id,
                $integration['icon_url'],
                $integration['name']
            );

        if ( is_wp_error( $attachment_id ) ) {
            WP_CLI::error( 'Failed to update post icon: ' . $attachment_id->get_error_message() );
        //     continue;
        } else {
            WP_CLI::log( 'Updated post icon with ID: ' . $attachment_id );
        }

        // we need to do something about the post parent ID.
        // the problem is that this is the ID from the old site.
        // if ( $integration['parent_id'] ) {
        //     $post->post_parent = $integration['parent_id'];
        //     wp_update_post( $post );
        //     WP_CLI::log( 'Updated post parent ID to: ' . $integration['parent_id'] );
        // }

        // Update the post with the icon and connection link.
        
        // update_field('learn_more_url', $connection_link, $post_id);

        }
    }

    // dup
    // public function update_post_icon( int $post_id = 0, string $url = '', string $name = '' ) {
    //     if ( ! $post_id || empty( $url ) ) {
    //         return new WP_Error( 'update_post_icon', 'Invalid post type or URL.' );
    //     }

    //     if ( empty( $name ) ) {
    //         $name = get_the_title( $post_id );
    //     }

    //     if ( ! bc_url_exists( $url ) ) {
    //         $attachment_id = $this->get_default_icon_id();
    //     } else {
    //         $attachment_id = $this->update_icon_in_media_library( $url, $name );
    //     }

    //     // Associate the attachment with the post.
    //     $field = apply_filters( 'bc_icon_field', 'icon_1', $post_id );
    //     update_field( $field, $attachment_id, $post_id );

    //     // Add the attachment to the Filebird folder.
    //     \BoomiCMS\BC_Filebird::get_instance()->add_attachment_to_folder( $attachment_id, 'Connectors' );

    //     return $attachment_id;
    // }
    
    // dup
    // public function get_default_icon_id() {
    //     return bc_get_attachment_id_from_filename( 'connector-icon-default.svg' );
    // }

    // // dup
    // private function update_icon_in_media_library( string $url = '', string $name = '' ) {
    //     $filename      = sanitize_file_name( $name ) . '.svg';
    //     $attachment_id = bc_file_exists_in_media_library( $filename );

    //     if ( $attachment_id ) {
    //         $attachment_id = bc_replace_media_file( $attachment_id, $url, $filename );
    //     } else {
    //         $attachment_id = bc_upload_file_to_media_library( $url, $filename );
    //     }

    //     return $attachment_id;
    // }    

}
