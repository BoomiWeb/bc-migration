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
use erikdmitchell\bcmigration\MapPostTaxonomies;
use erikdmitchell\bcmigration\traits\LoggerTrait;
use WP_Query;

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
	 * [--taxonomy-type=<type>]
	 * : The type of taxonomy to migrate.
	 * 
	 * [--file=<file_path>]
	 * : Path to a CSV file with post IDs to migrate.
	 *
	 * [--log=<name>]
     * : Name of the log file.
	 * 
	 * [--copy-tax]
	 * : Copy all post taxonomies.
	 * 
	 * [--tax-map=<file_path>]
	 * : Path to a JSON file with custom taxonomy mappings.
	 * 
	 * ## EXAMPLES
	 *
	 *     wp boomi migrate post-type --from=post --to=page --post_ids=177509,177510
	 *     wp boomi migrate post-type --from=post --to=page --taxonomy=api
	 *     wp boomi migrate post-type --file=/Users/erikmitchell/bc-migration/examples/post-type.csv
	 * 	   wp boomi migrate post-type --from=post --to=page --post_ids=188688 --copy-tax
	 *     wp boomi migrate post-type --from=post --to=page --post_ids=188688 --tax-map=/Users/erikmitchell/bc-migration/examples/post-type-tax-map.json
	 *
	 */    

	public function migrate( $args, $assoc_args ) {
		$from          = $assoc_args['from'] ?? null;
		$to            = $assoc_args['to'] ?? null;
		$term_slug     = $assoc_args['taxonomy'] ?? null;
		$taxonomy_type = $assoc_args['taxonomy-type'] ?? 'category';
		$log_name   = $assoc_args['log'] ?? 'migrate-post-type.log';
		$copy_tax      = isset( $assoc_args['copy-tax'] );
		$tax_map_file       = $assoc_args['tax-map'] ?? null;		

		if ( $log_name ) {			
            $this->set_log_name( $log_name );
        }  

		// Determine post IDs to migrate.
		if ( isset( $assoc_args['post_ids'] ) ) {
			$post_ids = array_map( 'intval', explode( ',', $assoc_args['post_ids'] ) );

			if ( empty( $post_ids ) ) {
				$this->log( 'No valid post IDs to migrate.', 'error' );
				$this->add_notice( 'No valid post IDs to migrate.', 'error' );

				return;
			}

			$this->change_post_type( $post_ids, $from, $to, $copy_tax, $tax_map_file );			
		} elseif ( $term_slug ) {					
			$post_ids = $this->get_post_ids_by_term( $from, $term_slug, $taxonomy_type );

			if ( false === $post_ids ) {
				$this->display_notices();

				return;
			}

			$this->change_post_type( $post_ids, $from, $to, $copy_tax, $tax_map_file );
			
			$this->log( "Migrated $term_slug posts", 'success' );
			$this->add_notice( "Migrated $term_slug posts", 'success' );
		} elseif ( isset( $assoc_args['file'] ) ) {			
			$file = $assoc_args['file'];

			if ( ! file_exists( $file ) ) {
				$this->log( "CSV file not found: $file", 'error' );
				$this->add_notice( "CSV file not found: $file", 'error' );

				return;
			}

			$this->process_csv_file( $file, $copy_tax );

			$this->log( "Processed $file", 'success' );
			$this->add_notice( "Processed $file", 'success' );
		}

		$this->display_notices();
	}

	private function process_csv_file(string $file, $copy_tax) {
		$rows    = array_map( 'str_getcsv', file( $file ) );
		$headers = array_map( 'trim', array_shift( $rows ) );

		if ( ! $this->validate_headers( $headers, array( 'from', 'to', 'post_ids' ) ) ) {	
			$this->log( "Invalid CSV headers: $file", 'error' );
			$this->add_notice( "Invalid CSV headers: $file", 'error' );

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
				$this->log( "Row $row_num: Skipped - one or more required fields missing.", 'warning' );
				$this->add_notice( "Row $row_num: Skipped - one or more required fields missing.", 'warning' );

				continue;
			}

			$from = $data['from'];
			$to = $data['to'];
			$post_ids = explode('|', $data['post_ids']);

			$this->change_post_type( $post_ids, $from, $to, $copy_tax );
		}
	}	

	private function change_post_type(array $post_ids, string $from, string $to, $copy_tax, $tax_map_file = null) {
		$count = 0;

		if ( empty( $post_ids ) ) {
			$this->log( 'No valid post IDs to migrate.', 'error' );
			$this->add_notice( 'No valid post IDs to migrate.', 'error' );
			
			return;
		}

		// Check valid post types.
		if ( ! $this->is_valid_post_type( $from ) ) {		
			$this->log( "`$from` is not a valid post type.", 'error' );
			$this->add_notice( "`$from` is not a valid post type.", 'error' );

			return;
		} else if ( ! $this->is_valid_post_type( $to ) ) {
			$this->log( "`$to` is not a valid post type.", 'error' );
			$this->add_notice( "`$to` is not a valid post type.", 'error' );

			return;
		}

		foreach ( $post_ids as $post_id ) {		
			$post = get_post( $post_id );
		
			if ( ! $post || $post->post_type !== $from ) {				
				$this->log( "Post $post_id is not a '$from' post type.", 'warning' );
				$this->add_notice( "Post $post_id is not a '$from' post type.", 'warning' );

				continue;
			}

			$updated = wp_update_post( [
				'ID'        => $post_id,
				'post_type' => $to,
			] );

			if (is_wp_error( $updated )) {
				$this->log( "Failed to update post $post_id.", 'warning' );
				$this->add_notice( "Failed to update post $post_id.", 'warning' );

				continue;
			}

			if ( $copy_tax ) {				
				$attached = $this->ensure_taxonomies_attached( $from, $to );

				if (! $attached ) {
					$this->log( "Taxonomies not attached.", 'warning' );
					$this->add_notice( "Taxonomies not attached.", 'warning' );

					continue;
				}

				$this->copy_tax( $post_id, $from );
			}

			if ($tax_map_file) {
				if ( ! file_exists( $tax_map_file ) ) {
					$this->log( "Mapping file not found: $tax_map_file", 'warning' );
					$this->add_notice( "Mapping file not found: $tax_map_file", 'warning' );
				}

				$tax_map = json_decode( file_get_contents( $tax_map_file ) );

				$this->tax_map( $post_id, $tax_map );

				continue;
			}

			$count++;
		}

		$this->log( "Migrated $count posts.", 'success' );
		$this->add_notice( "Migrated $count posts.", 'success' );

		return;
	}

	/**
	 * Get post IDs by term.
	 *
	 * @param string $from       Post type to retrieve posts from.
	 * @param string $term_slug  Term slug to retrieve posts by.
	 * @param string $taxonomy_type Taxonomy type to retrieve terms from.
	 *
	 * @return array|false Returns an array of post IDs or false if any of the checks fail.
	 */
	private function get_post_ids_by_term( string $from, string $term_slug, string $taxonomy_type ) {	
		if (! $this->is_valid_post_type( $from ) ) {
			$this->log( "`$from` is not a valid post type.", 'warning' );
			$this->add_notice( "`$from` is not a valid post type.", 'warning' );

			return false;
		}	
		
		if (!taxonomy_exists( $taxonomy_type ) ) {
			$this->log( "Taxonomy `$taxonomy_type` does not exist.", 'warning' );
			$this->add_notice( "Taxonomy `$taxonomy_type` does not exist.", 'warning' );

			return false;
		}

		if (!term_exists( $term_slug, $taxonomy_type ) ) {
			$this->log( "`$term_slug` does not exist in `$taxonomy_type`.", 'warning' );
			$this->add_notice( "`$term_slug` does not exist in `$taxonomy_type`.", 'warning' );

			return false;
		}

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
		
		return $query->posts;		
	}

// TODO: more detailed output	
	private function ensure_taxonomies_attached( $from, $to ) {
		$from_taxonomies = get_object_taxonomies( $from, 'objects' );

		if ( empty( $from_taxonomies ) ) {
			return false;
		}

		foreach ( $from_taxonomies as $taxonomy => $taxonomy_obj ) {
			if ( ! in_array( $to, $taxonomy_obj->object_type, true ) ) {
				$taxonomy_obj->object_type[] = $to;

				register_taxonomy( $taxonomy, $taxonomy_obj->object_type, (array) $taxonomy_obj );
		
				$this->log( "Attached taxonomy `$taxonomy` to `$to`." );
				$this->add_notice( "Attached taxonomy `$taxonomy` to `$to`." );
			}
		}
	}

	private function copy_tax(int $post_id, string $from) {
		$taxonomies = get_object_taxonomies( $from );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );

			if ( ! is_wp_error( $terms ) ) {
				wp_set_object_terms( $post_id, $terms, $taxonomy );

				$this->log( "Copied terms from `$from`." );
				$this->add_notice( "Copied terms from `$from`." );
			}

			// TODO: return error
		}
	}

	private function tax_map( int $post_id, array $tax_map ) {					
		$tax_terms = array();

		foreach ( $tax_map as $obj ) {
			$tax_mapper = new MapPostTaxonomies( $obj->from, $obj->to, $post_id );
			$mapped_term_ids = $tax_mapper->get_mapped_term_ids();
			$unmapped_term_ids = $tax_mapper->get_unmapped_term_ids();

			if ( !empty($unmapped_term_ids) ) {
				foreach ( $unmapped_term_ids as $term_id ) {
					$term = get_term($term_id);

					$new_term_data = wp_insert_term( $term->name, $obj->to, array( 'slug' => $term->slug ) );

					if ( is_wp_error( $new_term_data ) ) {
						continue;
					}

					$tax_terms[] = $new_term_data['term_id'];
				}
			}

			if ( !empty($mapped_term_ids) ) {
				foreach ( $mapped_term_ids as $term_id ) {
					$tax_terms[] = $term_id;
				}
			}
		}			

		$set_term_ids = wp_set_object_terms( $post_id, $tax_terms, $obj->to );

		// TODO: remove old terms

		if ( is_wp_error( $set_term_ids ) ) {
			$this->log( $set_term_ids->get_error_message(), 'warning' );
			$this->add_notice( $set_term_ids->get_error_message(), 'warning' );
		}

		$this->log( "Copied terms from `$obj->from` to `$obj->to`." );
		$this->add_notice( "Copied terms from `$obj->from` to `$obj->to`." );
	}
	
	private function taxonomy_term_exists( string $term, string $tax ) {
		$term_obj = get_term_by( is_numeric( $term ) ? 'id' : 'slug', $term, $tax );

		return ( $term_obj && ! is_wp_error( $term_obj ) ) ? $term_obj : false;
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
            $this->add_notice( 'CSV is missing required columns: ' . implode( ', ', $missing ), 'warning' );

            $this->log( 'CSV is missing required columns: ' . implode( ', ', $missing ), 'warning' );

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

    /**
     * Checks if the given post type exists.
     *
     * @param string $post_type The post type to check.
     *
     * @return bool Returns true if the post type exists, false otherwise.
     */
	private function is_valid_post_type( string $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return false;
		}

		return true;
	}

	private function get_existing_term( $term, $taxonomy ) {
		if ( is_numeric( $term ) ) {
			$term_obj = get_term_by( 'id', $term, $taxonomy );
		} else {
			$term_obj = get_term_by( 'slug', $term, $taxonomy );
			if ( ! $term_obj ) {
				$term_obj = get_term_by( 'name', $term, $taxonomy );
			}
		}
	
		return ( $term_obj && ! is_wp_error( $term_obj ) ) ? $term_obj : false;
	}
}