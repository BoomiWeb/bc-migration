<?php

class Migrate_Posts_Command {

	public function __invoke( $args, $assoc_args ) {
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

WP_CLI::add_command( 'myplugin migrate_posts', 'Migrate_Posts_Command' );