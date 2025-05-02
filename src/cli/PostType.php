<?php
/**
 * Post Type CLI class
 *
 * @package erikdmitchell\bcmigration\cli
 * @since   0.3.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli;

use erikdmitchell\bcmigration\abstracts\CLICommands;
use erikdmitchell\bcmigration\subprocessor\MigrateSubscribeData;
use WP_CLI;

/*
wp myplugin migrate_posts --from=old_post_type --to=new_post_type --post_ids=1,2,3,4
wp myplugin migrate_posts --from=old_post_type --to=new_post_type --taxonomy=category-slug
wp myplugin migrate_posts --from=old_post_type --to=new_post_type --csv=/path/to/file.csv

# Basic migration with meta and taxonomies
wp myplugin migrate_posts --from=book --to=article --post_ids=1,2,3 --copy-meta --copy-tax

# Migrate via taxonomy slug
wp myplugin migrate_posts --from=book --to=article --taxonomy=fiction --copy-meta --copy-tax

# Migrate via CSV
wp myplugin migrate_posts --from=book --to=article --csv=ids.csv --copy-meta --copy-tax

# Migrate and copy meta/tax, auto-attach taxonomies to destination
wp myplugin migrate_posts --from=book --to=article --post_ids=1,2,3 --copy-meta --copy-tax

# Migrate by post ID
wp myplugin migrate_posts --from=book --to=article --post_ids=1,2,3 --copy-meta --copy-tax

# Migrate posts from the 'fiction' term in the 'genre' taxonomy
wp myplugin migrate_posts --from=book --to=article --taxonomy=fiction --taxonomy-type=genre --copy-meta --copy-tax

# Migrate via CSV
wp myplugin migrate_posts --from=book --to=article --csv=posts.csv --copy-meta --copy-tax
*/

class PostType {

	/**
	 * Migrate posts from one custom post type to another.
	 *
	 * ## OPTIONS
	 *
	 * [--from=<post_type>]
	 * : The current post type.
	 *
	 * [--to=<post_type>]
	 * : The new post type to migrate to.
	 *
	 * [--post_ids=<ids>]
	 * : A comma-separated list of post IDs to migrate.
	 *
	 * [--taxonomy=<slug>]
	 * : A taxonomy term slug to migrate all matching posts.
	 *
	 * [--csv=<file_path>]
	 * : Path to a CSV file with post IDs to migrate.
	 *
	 * ## EXAMPLES
	 *
	 *     wp myplugin migrate_posts --from=book --to=article --post_ids=1,2,3
	 *     wp myplugin migrate_posts --from=book --to=article --taxonomy=fiction
	 *     wp myplugin migrate_posts --from=book --to=article --csv=ids.csv
	 *
	 */    

	public function migrate( $args, $assoc_args ) {
		$from          = $assoc_args['from'] ?? null;
		$to            = $assoc_args['to'] ?? null;
		$term_slug     = $assoc_args['taxonomy'] ?? null;
		$taxonomy_type = $assoc_args['taxonomy-type'] ?? 'category';
		$copy_meta     = isset( $assoc_args['copy-meta'] );
		$copy_tax      = isset( $assoc_args['copy-tax'] );
		$post_ids      = [];

		if ( ! $from || ! $to ) {
			WP_CLI::error( '`--from` and `--to` post types are required.' );
		}

		// Determine post IDs to migrate
		if ( isset( $assoc_args['post_ids'] ) ) {
			$post_ids = array_map( 'intval', explode( ',', $assoc_args['post_ids'] ) );
		} elseif ( $term_slug ) {
			$query = new WP_Query( [
				'post_type'      => $from,
				'posts_per_page' => -1,
				'tax_query'      => [
					[
						'taxonomy' => $taxonomy_type,
						'field'    => 'slug',
						'terms'    => $term_slug,
					],
				],
				'fields' => 'ids',
			] );
			$post_ids = $query->posts;
		} elseif ( isset( $assoc_args['csv'] ) ) {
			$csv_path = $assoc_args['csv'];
			if ( ! file_exists( $csv_path ) ) {
				WP_CLI::error( "CSV file not found: $csv_path" );
			}
			$file = fopen( $csv_path, 'r' );
			while ( ( $line = fgetcsv( $file ) ) !== false ) {
				foreach ( $line as $id ) {
					if ( is_numeric( $id ) ) {
						$post_ids[] = (int) $id;
					}
				}
			}
			fclose( $file );
		}

		if ( empty( $post_ids ) ) {
			WP_CLI::error( 'No valid post IDs to migrate.' );
		}

		// Ensure taxonomies are registered on the target post type
		if ( $copy_tax ) {
			$this->ensure_taxonomies_attached( $from, $to );
		}

		$count = 0;
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== $from ) {
				continue;
			}

			wp_update_post( [
				'ID'        => $post_id,
				'post_type' => $to,
			] );

			if ( $copy_meta ) {
				$meta = get_post_meta( $post_id );
				foreach ( $meta as $key => $values ) {
					foreach ( $values as $value ) {
						add_post_meta( $post_id, $key, maybe_unserialize( $value ), false );
					}
				}
			}

			if ( $copy_tax ) {
				$taxonomies = get_object_taxonomies( $from );
				foreach ( $taxonomies as $taxonomy ) {
					$terms = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );
					if ( ! is_wp_error( $terms ) ) {
						wp_set_object_terms( $post_id, $terms, $taxonomy );
					}
				}
			}

			$count++;
		}

		WP_CLI::success( "Migrated $count posts from `$from` to `$to`." );
	}

	private function ensure_taxonomies_attached( $from, $to ) {
		$from_taxonomies = get_object_taxonomies( $from, 'objects' );
		foreach ( $from_taxonomies as $taxonomy => $taxonomy_obj ) {
			if ( ! in_array( $to, $taxonomy_obj->object_type, true ) ) {
				$taxonomy_obj->object_type[] = $to;
				register_taxonomy( $taxonomy, $taxonomy_obj->object_type, (array) $taxonomy_obj );
				WP_CLI::log( "Attached taxonomy `$taxonomy` to `$to`." );
			}
		}
	}
}