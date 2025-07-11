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
        
            WP_CLI::log( 'Migrating integration: ' . $integration['name'] . ' (ID: ' . $post_id . ')' );

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

            // update the taxonomies.
            if ( ! empty( $integration['integration_category'] ) ) {
                $cats = $this->update_post_category( $post_id, $integration['integration_category'] );
            }

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
            $term_id = 0;

            // Check if the term exists by name in the 'business-function' taxonomy.
            $existing_term = get_term_by( 'name', $cat['name'], 'business-function' );

            // Term exists, use $existing_term->term_id as needed.
            if ( $existing_term && ! is_wp_error( $existing_term ) ) {
                $term_id = $existing_term->term_id;
            } else {
                // Term does not exist, create it.
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

                $term_id = is_array( $term ) ? $term['term_id'] : $term;
            }

            update_term_meta( $term_id, '_bcm_rivery_integration_category_id', $cat['term_id'] );
        }
    }
    
    private function update_post_parent($post_id) {
        global $wpdb;

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

    private function update_post_category($post_id, $cat_ids) {
        $categories = array();

        foreach ( $cat_ids as $cat_id ) {
            // Check if the term exists by ID in the 'business-function' taxonomy.
            $term_id = $this->get_term_id_by_meta( '_bcm_rivery_integration_category_id', $cat_id, 'business-function' );

            if ( $term_id ) {
                $categories[] = (int) $term_id;
            }
        }

        wp_set_object_terms( $post_id, $categories, 'business-function' );

        return $categories;
    }

    private function get_term_id_by_meta($meta_key, $meta_value, $taxonomy) {
        global $wpdb;

        return $wpdb->get_var( $wpdb->prepare(
            "SELECT tm.term_id
            FROM $wpdb->termmeta tm
            INNER JOIN $wpdb->term_taxonomy tt ON tm.term_id = tt.term_id
            WHERE tm.meta_key = %s AND tm.meta_value = %s AND tt.taxonomy = %s
            LIMIT 1",
            $meta_key,
            $meta_value,
            $taxonomy
        ) );
    }

}