<?php
/**
 * Rivery migrate integrations CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.3.4
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\rivery;

use erikdmitchell\bcmigration\rivery\import\IntegrationCategoryTax;
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

        $this->import_integration_categories();
        $integrations = Integrations::init()->get_integrations();

        if ( is_wp_error( $integrations ) ) {
            WP_CLI::error( 'Failed to fetch Rivery integrations: ' . $integrations->get_error_message() );
            return;
        }

        if ( empty( $integrations ) ) {
            WP_CLI::success( 'No integrations found to migrate.' );
            return;
        }
// TODO: add progress bar
// https://make.wordpress.org/cli/handbook/references/internal-api/wp-cli-utils-make-progress-bar/
        $formatted_integrations = Integrations::init()->format_integrations( $integrations );
print_r($formatted_integrations);

return;


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
        
            WP_CLI::log( 'Migrating integration: ' . $integration['name'] );

            // Import the icon and update the post.
            $attachment_id = \BoomiCMS\Connectors\Import\ConnectorIcons::get_instance()->update_post_icon(
                $post_id,
                $integration['icon_url'],
                $integration['name']
            );

            // FIXME: does not work
            // $connection_link = 'https://docs.rivery.io/docs/connection-'. str_replace('_', '-', $integration['slug']);
            // update_field('learn_more_url', $connection_link, $post_id);

            // we need to do something about the post parent ID.
            // the problem is that this is the ID from the old site.
            update_post_meta( $post_id, '_bcm_rivery_post_id', $integration['post_id'] );
            update_post_meta( $post_id, '_bcm_rivery_parent_post_id', $integration['parent_id'] );

            // handle taxonomies

            // update the post parent ID.
            if ( ! empty( $integration['parent_id'] ) ) {
                $this->update_post_parent( $post_id );
            }
        }
    }

    public function import_integration_categories() {
        $cats = IntegrationCategoryTax::init()->get();

        if ( is_wp_error( $cats ) ) {
            // WP_CLI::error( 'Failed to fetch Rivery integration categories: ' . $cats->get_error_message() );
            
            return;
        }

        if (empty($cats)) {
            // WP_CLI::success( 'No integration categories found to migrate.' );

            return;
        }

        foreach ( $cats as $cat ) {
            $term = wp_insert_term(
                $cat['name'],
                'business-function',
                array(
                    'description' => $cat['description'] ?? '',
                    'slug'        => $cat['slug'] ?? '',
                )
            );

            if ( is_wp_error( $term ) ) {
                // WP_CLI::warning( 'Failed to insert integration category ' . $cat['name'] . ': ' . $term->get_error_message() );

                continue;
            }

            // WP_CLI::log( 'Migrated integration category: ' . $cat['name'] );
        }
    }
    
    private function update_post_parent($post_id) {
        $parent_id = get_post_meta( $post_id, '_bcm_rivery_parent_post_id', true );
    
        if ( ! empty( $parent_id ) ) {
            $parent_post_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                '_bcm_rivery_post_id',
                $parent_id
            ) );

            if ( $parent_post_id ) {
                wp_update_post( array(
                    'ID' => $post_id,
                    'post_parent' => $parent_post_id,
                ) );
            }
        }

        delete_post_meta( $post_id, '_bcm_rivery_post_id' );
        delete_post_meta( $post_id, '_bcm_rivery_parent_post_id' );
    }
}