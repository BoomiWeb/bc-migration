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
use erikdmitchell\bcmigration\traits\LoggerTrait;
use WP_CLI;
use WP_Query;

/*

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

class PostType extends CLICommands {

	use LoggerTrait;

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
	 * [--file=<file_path>]
	 * : Path to a CSV file with post IDs to migrate.
	 *
	 * ## EXAMPLES
	 *
	 *     wp boomi migrate post-type --from=post --to=page --post_ids=177509,177510
	 *     wp boomi migrate post-type --from=post --to=page --taxonomy=api
	 *     wp boomi migrate post-type --from=post --to=page --file=/Users/erikmitchell/bc-migration/examples/post-type.csv
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

		// TODO: check valid post types

		// FIXME: add param
		$log_name   = $assoc_args['log'] ?? null;

        if ( $log_name ) {
            $this->set_log_name( $log_name );
        }  

		// Determine post IDs to migrate.
		if ( isset( $assoc_args['post_ids'] ) ) {
			$post_ids = array_map( 'intval', explode( ',', $assoc_args['post_ids'] ) );
print_r($post_ids);			
		} elseif ( $term_slug ) {
echo "term slug\n";			
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
		} elseif ( isset( $assoc_args['file'] ) ) {
echo "csv\n";			
			$file = $assoc_args['file'];
// echo "file: $file\n";
			if ( ! file_exists( $file ) ) {
				WP_CLI::error( "CSV file not found: $file" );
			}

			$rows    = array_map( 'str_getcsv', file( $file ) );
			$headers = array_map( 'trim', array_shift( $rows ) );
	
			if ( ! $this->validate_headers( $headers, array( 'from', 'to', 'post_ids' ) ) ) {
// echo "bad headers\n";				
				return;
			}
	
			foreach ( $rows as $i => $row ) {
				$row_num  = $i + 2;
				$data     = array_combine( $headers, $row );
				$data     = array_map( 'trim', $data );
print_r($data);				

				// skip empty lines.
				if ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) {
					continue;
				}

				// Check required fields.
				if ( ! $this->has_required_fields( $data, array( 'from', 'to', 'post_ids' ), $row_num ) ) {
					continue;
				}

				$from = $data['from'];
				$to = $data['to'];
				$post_ids = explode('|', $data['post_ids']);

echo "from: $from > to: $to\n";
print_r($post_ids);
			}			
/*
			$file = fopen( $csv_path, 'r' );

			while ( ( $line = fgetcsv( $file ) ) !== false ) {
print_r($line);				
				foreach ( $line as $id ) {
					if ( is_numeric( $id ) ) {
						$post_ids[] = (int) $id;
					}
				}
			}

			fclose( $file );
			*/
		}

		if ( empty( $post_ids ) ) {
			WP_CLI::error( 'No valid post IDs to migrate.' );
		}

		// Ensure taxonomies are registered on the target post type
		if ( $copy_tax ) {
echo "copy tax\n";			
			$this->ensure_taxonomies_attached( $from, $to );
		}

		$count = 0;

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
// print_r($post);			
			if ( ! $post || $post->post_type !== $from ) {
echo "post type is not the from value\n";				
				continue;
			}
echo "$post_id > $to\n";
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

    /**
     * Validates that the given array of CSV headers contains all required fields.
     *
     * @param string[] $headers  The array of CSV headers.
     * @param string[] $required The list of required field keys.
     *
     * @return bool Returns true if all required fields are present, false otherwise.
     */
    protected function validate_headers( array $headers, array $required ) {
        $missing = array_diff( $required, $headers );

        if ( ! empty( $missing ) ) {
            $this->add_notice( 'CSV is missing required columns: ' . implode( ', ', $missing ), 'error' );

            $this->log( 'CSV is missing required columns: ' . implode( ', ', $missing ) );

            return false;
        }

        return true;
    }

    /**
     * Checks if the given data array contains all required fields.
     *
     * @param array<string, mixed> $data     The data array to check.
     * @param string[]             $required The list of required field keys.
     * @param int                  $row_num  The row number for logging purposes. Defaults to 0.
     *
     * @return bool Returns true if all required fields are present, false otherwise.
     */
    protected function has_required_fields( array $data, array $required, int $row_num = 0 ) {
        $missing_keys = array_diff_key( array_flip( $required ), $data );

        if ( ! empty( $missing_keys ) ) {
            // TODO: add message.
            $this->add_notice( "Row $row_num: Skipped - one or more required fields missing.", 'warning' );  // TODO: add check for row number.

            $this->log( "Row $row_num: Skipped - one or more required fields missing." );

            return false;
        }

        return true;
    }
}