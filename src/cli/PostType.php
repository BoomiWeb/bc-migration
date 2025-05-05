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
	 * [--log=<name>]
     * : Name of the log file.
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
		$log_name   = $assoc_args['log'] ?? 'migrate-post-type.log';

		if ( ! $from || ! $to ) {
			WP_CLI::error( '`--from` and `--to` post types are required.' );
		}

		if ( $log_name ) {			
            $this->set_log_name( $log_name );
        }  

		// Determine post IDs to migrate.
		if ( isset( $assoc_args['post_ids'] ) ) {
			$post_ids = array_map( 'intval', explode( ',', $assoc_args['post_ids'] ) );

			if ( empty( $post_ids ) ) {
				WP_CLI::error( 'No valid post IDs to migrate.' );
			}

			$this->change_post_type( $post_ids, $from, $to, $copy_meta, $copy_tax );

			// TODO: success or error message
			return;			
		} elseif ( $term_slug ) {
			$post_ids = $this->get_post_ids_by_term( $term_slug, $from, $taxonomy_type );

			$this->change_post_type( $post_ids, $from, $to, $copy_meta, $copy_tax );

			// TODO: success or error message
			return;			
		} elseif ( isset( $assoc_args['file'] ) ) {			
			$file = $assoc_args['file'];

			if ( ! file_exists( $file ) ) {
				WP_CLI::error( "CSV file not found: $file" );
			}

			$this->process_csv_file( $file, $copy_meta, $copy_tax );

			WP_CLI::success( "CSV file processed: $file" );
			
			// TODO: success or error message
			return;
		}
	}

	private function process_csv_file(string $file, $copy_meta, $copy_tax) {
		$rows    = array_map( 'str_getcsv', file( $file ) );
		$headers = array_map( 'trim', array_shift( $rows ) );

		if ( ! $this->validate_headers( $headers, array( 'from', 'to', 'post_ids' ) ) ) {	
			$this->log( "Invalid CSV headers: $file" );

			WP_CLI::error( "Invalid CSV headers: $file" );

			return;
		}

		foreach ( $rows as $i => $row ) {
			$row_num  = $i + 2;
			$data     = array_combine( $headers, $row );
			$data     = array_map( 'trim', $data );				

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

			$this->change_post_type( $post_ids, $from, $to, $copy_meta, $copy_tax );
		}
	}	

// TODO: more detailed output
	private function change_post_type(array $post_ids, string $from, string $to, $copy_meta, $copy_tax) {
		$count = 0;

		if ( empty( $post_ids ) ) {
			$this->log( 'No valid post IDs to migrate.' );
			WP_CLI::error( 'No valid post IDs to migrate.' );
		}

		// Check valid post types.
		if ( ! $this->is_valid_post_type( $from ) ) {
			$this->log( "`$from` is not a valid post type." );
			WP_CLI::error( "`$from` is not a valid post type." );
		} else if ( ! $this->is_valid_post_type( $to ) ) {
			$this->log( "`$to` is not a valid post type." );
			WP_CLI::error( "`$to` is not a valid post type." );
		}

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
		
			if ( ! $post || $post->post_type !== $from ) {
				$this->log( "Post $post_id is not a $from post." );

				continue;
			}

			wp_update_post( [
				'ID'        => $post_id,
				'post_type' => $to,
			] );

			if ( $copy_meta ) {
				$this->copy_meta( $post_id );
			}

			if ( $copy_tax ) {
				$this->ensure_taxonomies_attached( $from, $to );
				// TODO: check return before moving forward
				$this->copy_tax( $post_id, $from );
			}

			$count++;
		}

		return $count;
	}

	private function get_post_ids_by_term( string $from, string $term_slug, string $taxonomy_type ) {		
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

		// TODO: more detailed output & validate query
		
		return $query->posts;		
	}

// TODO: more detailed output	
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

	// TODO: more detailed output
	private function copy_meta(int $post_id) {
		$meta = get_post_meta( $post_id );

		foreach ( $meta as $key => $values ) {
			foreach ( $values as $value ) {
				add_post_meta( $post_id, $key, maybe_unserialize( $value ), false );
			}
		}
	}

	// TODO: more detailed output
	private function copy_tax(int $post_id, string $from) {
		$taxonomies = get_object_taxonomies( $from );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );

			if ( ! is_wp_error( $terms ) ) {
				wp_set_object_terms( $post_id, $terms, $taxonomy );
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

	private function is_valid_post_type( string $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return false;
		}

		return true;
	}
}

/*
if ( is_wp_error( $result ) ) {
	$this->add_notice( 'Error - ' . $result->get_error_message(), 'warning' );
} else {
	$this->log( 'Merged ' . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)" );
	$this->add_notice( 'Merged ' . implode( ', ', $from_terms ) . " into $to_term ($taxonomy)", 'success' );
}
*/