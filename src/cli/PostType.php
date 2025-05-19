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
use erikdmitchell\bcmigration\mapping\MapPostData;
use erikdmitchell\bcmigration\traits\LoggerTrait;
use erikdmitchell\bcmigration\managers\PostDataManager;
use erikdmitchell\bcmigration\mapping\MapACFFields;
use WP_Query;

/**
 * PostType class
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
	 * [--meta-map=<file_path>]
	 * : Path to a JSON file with custom meta mappings.
	 * ## EXAMPLES
	 *
	 *     wp boomi migrate post-type --from=post --to=page --post_ids=177509,177510
	 *     wp boomi migrate post-type --from=post --to=page --taxonomy=api
	 *     wp boomi migrate post-type --file=/Users/erikmitchell/bc-migration/examples/post-type.csv
	 *     wp boomi migrate post-type --from=post --to=page --post_ids=188688 --copy-tax
	 *     wp boomi migrate post-type --from=post --to=page --post_ids=188688 --tax-map=/Users/erikmitchell/bc-migration/examples/post-type-tax-map.json
	 *     wp boomi migrate post-type --from=post --to=page --post_ids=188932 --meta-map=/Users/erikmitchell/bc-migration/examples/post-type-meta-map.json
	 *
	 * @param string[]             $args       CLI positional arguments.
	 * @param array<string, mixed> $assoc_args CLI associative arguments.
	 *
	 * @return void
	 */
	public function migrate( $args, $assoc_args ) {
		$from          = $assoc_args['from'] ?? null;
		$to            = $assoc_args['to'] ?? null;
		$term_slug     = $assoc_args['taxonomy'] ?? null;
		$taxonomy_type = $assoc_args['taxonomy-type'] ?? 'category';
		$log_name      = $assoc_args['log'] ?? 'migrate-post-type.log';
		$copy_tax      = isset( $assoc_args['copy-tax'] );
		$tax_map_file  = $assoc_args['tax-map'] ?? null;
		$meta_map_file = $assoc_args['meta-map'] ?? null;
	
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

			$this->change_post_type( $post_ids, $from, $to, $copy_tax, $tax_map_file, $meta_map_file );
		} elseif ( $term_slug ) {
			$post_ids = $this->get_post_ids_by_term( $from, $term_slug, $taxonomy_type );

			if ( false === $post_ids ) {
				$this->display_notices();

				return;
			}

			$this->change_post_type( $post_ids, $from, $to, $copy_tax, $tax_map_file, $meta_map_file );

			$this->log( "Migrated $term_slug posts", 'success' );
			$this->add_notice( "Migrated $term_slug posts", 'success' );
		} elseif ( isset( $assoc_args['file'] ) ) {
			$file = $assoc_args['file'];

			if ( ! file_exists( $file ) ) {
				$this->log( "CSV file not found: $file", 'error' );
				$this->add_notice( "CSV file not found: $file", 'error' );

				$this->display_notices();

				return;
			}

			$this->process_csv_file( $file, $copy_tax, $tax_map_file, $meta_map_file );

			$this->log( "Processed $file", 'success' );
			$this->add_notice( "Processed $file", 'success' );
		}

		$this->display_notices();
	}

	/**
	 * Processes a CSV file to change post types for specified post IDs.
	 *
	 * Reads a CSV file containing post IDs along with their current and target post types,
	 * validates the headers, and performs post type migration for each valid row.
	 *
	 * @param string      $file         The path to the CSV file containing migration data.
	 * @param bool        $copy_tax     Whether to copy taxonomies during migration.
	 * @param string|null $tax_map_file The path to a JSON file containing a taxonomy mapping.
	 * @param string|null $meta_map_file The path to a JSON file containing a meta mapping.
	 *
	 * @return void
	 */
	private function process_csv_file( string $file, $copy_tax, $tax_map_file, $meta_map_file ) {
		$rows = array_map(
			function ( $line ) {
				return str_getcsv( $line, ',', '"', '\\' );
			},
			file( $file )
		);

		$headers = array_map( 'trim', array_shift( $rows ) );

		if ( ! $this->validate_headers( $headers, array( 'from', 'to', 'post_ids' ) ) ) {
			$this->log( "Invalid CSV headers: $file", 'error' );
			$this->add_notice( "Invalid CSV headers: $file", 'error' );

			return;
		}

		foreach ( $rows as $i => $row ) {
			$row_num = $i + 2;
			$data    = array_combine( $headers, $row );
			$data    = array_map( 'trim', $data );

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

			$from     = $data['from'];
			$to       = $data['to'];
			$post_ids = explode( '|', $data['post_ids'] );

			$this->change_post_type( $post_ids, $from, $to, $copy_tax, $tax_map_file, $meta_map_file );
		}
	}

	/**
	 * Change the post type for a given array of post IDs.
	 *
	 * Logs and adds notices for any errors or skipped posts.
	 *
	 * @param array       $post_ids The post IDs to migrate.
	 * @param string      $from The current post type.
	 * @param string      $to The new post type.
	 * @param bool        $copy_tax Whether to copy taxonomies.
	 * @param string|null $tax_map_file The path to a JSON file containing a taxonomy mapping.
	 * @param string|null $meta_map_file The path to a JSON file containing a meta mapping.
	 */
	private function change_post_type( array $post_ids, string $from, string $to, $copy_tax, $tax_map_file = null, $meta_map_file = null ) {
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
		} elseif ( ! $this->is_valid_post_type( $to ) ) {
			$this->log( "`$to` is not a valid post type.", 'error' );
			$this->add_notice( "`$to` is not a valid post type.", 'error' );

			return;
		}

		// TODO: possibly split this out into a separate method and or functions.
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );

			if ( is_wp_error( $post ) ) {
				$this->log( "Failed to get post $post_id.", 'warning' );
				$this->add_notice( "Failed to get post $post_id.", 'warning' );

				continue;
			}

			if ( ! $post || $post->post_type !== $from ) {
				$this->log( "Post $post_id is not a '$from' post type.", 'warning' );
				$this->add_notice( "Post $post_id is not a '$from' post type.", 'warning' );

				continue;
			}

			$merge = false;
			$to_post_id = $this->post_exists( $post->post_name, $to );
			
			if ($to_post_id) {
				// echo "we have a match for $post_id to $to ($to_post_id)\n";
				$merge = true; // FIXME: should be passed
				$updated = $to_post_id;
			} else {
				// echo "update post type for $post_id to $to\n";
				// $updated = 1; // tmp
				$updated = $this->update_post_type( $post_id, $to );
			}

			if ( is_wp_error( $updated ) ) {
				$this->log( "Failed to update post $post_id.", 'warning' );
				$this->add_notice( "Failed to update post $post_id.", 'warning' );

				continue;
			}
// echo "to post id: $to_post_id\n";
			$this->handle_meta_and_tax( array(
				'copy_tax'      => $copy_tax,
				'tax_map_file' => $tax_map_file,
				'meta_map_file' => $meta_map_file,
				'post_id'      => $post_id,
				'from'         => $from,
				'to'           => $to,
				'merge'        => $merge, // FIXME: this should just be merge and thus it would merge meta/tax and delete post below
				'to_post_id'   => $to_post_id,
				'force'        => true // TODO add support for force.
			) );

			// TODO: true should be a param - so should delete or not.
			// if ($merge_meta_tax) {
			// 	$deleted = wp_delete_post( $post_id, true );

			// 	if ( is_wp_error( $deleted ) ) {
			// 		$this->log( $deleted->get_error_message(), 'warning' );
			// 		$this->add_notice( $deleted->get_error_message(), 'warning' );
			// 	}
			// }			

			++$count;
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
		if ( ! $this->is_valid_post_type( $from ) ) {
			$this->log( "`$from` is not a valid post type.", 'warning' );
			$this->add_notice( "`$from` is not a valid post type.", 'warning' );

			return false;
		}

		if ( ! taxonomy_exists( $taxonomy_type ) ) {
			$this->log( "Taxonomy `$taxonomy_type` does not exist.", 'warning' );
			$this->add_notice( "Taxonomy `$taxonomy_type` does not exist.", 'warning' );

			return false;
		}

		if ( ! term_exists( $term_slug, $taxonomy_type ) ) {
			$this->log( "`$term_slug` does not exist in `$taxonomy_type`.", 'warning' );
			$this->add_notice( "`$term_slug` does not exist in `$taxonomy_type`.", 'warning' );

			return false;
		}

		$query = new WP_Query(
			array(
				'post_type'      => $from,
				'posts_per_page' => -1,
				'tax_query'      => array(
					array(
						'taxonomy' => $taxonomy_type,
						'field'    => 'slug',
						'terms'    => $term_slug,
					),
				),
				'fields'         => 'ids',
			)
		);

		return $query->posts;
	}

	private function handle_meta_and_tax(array $args) {
		$defaults = array(
			'copy_tax'      => false,
			'tax_map_file' => '',
			'meta_map_file' => '',
			'post_id' => 0,
			'from' => '',
			'to' => '',
			'merge' => false,
			'to_post_id' => 0
		);
		$args = wp_parse_args( $args, $defaults );
// print_r($args);
		$copy_tax      = $args['copy_tax'];
		$tax_map_file  = $args['tax_map_file'];
		$meta_map_file = $args['meta_map_file'];
		$post_id       = $args['post_id'];
		$from          = $args['from'];
		$to            = $args['to'];
		$merge         = $args['merge'];
		$to_post_id    = $args['to_post_id'];
// echo "handle_meta_and_tax::to_post_id: $to_post_id\n";
		if ( $copy_tax ) {
echo "copy_tax\n";			
			$this->copy_tax( $post_id, $from, $to );
		}

		if ( $tax_map_file ) {
echo "tax_map_file\n";			
			$this->update_taxonomies( $post_id, $tax_map_file, $merge, $to_post_id );
		}

		if ( $meta_map_file ) {		
			$this->update_meta( $post_id, $meta_map_file, $from, $merge, $to_post_id );
		}
	}

	protected function update_meta(int $post_id, string $file, string $from, bool $merge = false, int $to_post_id = 0 ) {
		if ( ! file_exists( $file ) ) {
			$this->log( "Mapping file not found: $file", 'warning' );
			$this->add_notice( "Mapping file not found: $file", 'warning' );

			return;
		}

		// check the map for the post type.
		$meta_map          = array();
		$post_type_to_find = $from;
		$mappings          = json_decode( file_get_contents( $file ), true );

		foreach ( $mappings as $mapping ) {
			if ( isset( $mapping['post_type'] ) && $mapping['post_type'] === $post_type_to_find ) {
				$meta_map = $mapping['meta_map'];

				break; // Stop at the first match.
			}
		}

		if ( empty( $meta_map ) ) {
			$this->log( "Mapping not found for post type: $post_type_to_find", 'warning' );
			$this->add_notice( "Mapping not found for post type: $post_type_to_find", 'warning' );

			return;
		}

		// Get the mapped values.
		$mapper = new MapPostData( $this );
		$mapped_data = $mapper->map( $post_id, $meta_map, $to_post_id ); // TODO: pass merge var

		// Check $merge for correct post id.
		if ( $merge ) {
			$post_id = $to_post_id;
		}

		// loop through the mapped data.
		foreach ( $mapped_data as $map_arr ) {						
			$updated_value = PostDataManager::update_field_value( array(
				'post_id' => $post_id,
				'type' => $map_arr['type'],
				'key' => $map_arr['key'],
				'value' => $map_arr['value'],
			) );

			// TODO: add wp error check
// echo $map_arr['key'] . " - updated_value\n";			
// print_r( $updated_value );			
		}				

			// TODO: add param or flag
// echo "delete old meta commented out\n";			
			// PostDataManager::delete_field_value( $post_id, $from_field_key, $from_field_type );
			// $this->delete_old_meta($post_id, $from_field_key, $from_field_type);
// echo "update log and notice\n";
			// $this->log( "Copied `$from_field_key` from `$from_field_type` to `$to_field_key` in `$to_field_type`.", 'success' );
			// $this->add_notice( "Copied `$from_field_key` from `$from_field_type` to `$to_field_key` in `$to_field_type`.", 'success' );		
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
			$this->log( "Row $row_num: Skipped - one or more required fields missing." );
			$this->add_notice( "Row $row_num: Skipped - one or more required fields missing.", 'warning' );  // TODO: add check for row number.

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

	/**
	 * Checks if a post exists by slug and post type.
	 *
	 * @param string $slug      The post slug to check.
	 * @param string $post_type The post type to check.
	 *
	 * @return int|false Returns the post ID if the post exists, false otherwise.
	 */
	private function post_exists(string $slug, string $post_type) {
		$post = get_page_by_path( $slug, 'OBJECT', $post_type );

		if ( $post instanceof \WP_Post ) {
			return $post->ID;
		}

		return false;
	}

	/**
	 * Updates a post's type and optional additional fields.
	 *
	 * @param int    $post_id The post ID to update.
	 * @param string $post_type The post type to set.
	 * @param array  $args     Optional. Additional fields to update. Defaults to an empty array.
	 *
	 * @return int|WP_Error The post ID if successful, or a WP_Error on failure.
	 */
	private function update_post_type(int $post_id, string $post_type, array $args = []) {
		$post_arr = array(
			'ID'        => $post_id,
			'post_type' => $post_type,
		);

		foreach ($args as $key => $value) {
			$post_arr[$key] = $value;
		}

		return wp_update_post($post_arr);		
	}
}
