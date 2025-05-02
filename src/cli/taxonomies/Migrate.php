<?php
/**
 * Migrate Taxonomies CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.1
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;
use WP_Error;

/**
 * Migrate Class
 */
class Migrate extends TaxonomyCLICommands {
	/**
	 * Migrate terms between different Taxonomies.
	 *
	 * ## OPTIONS
	 *
	 * [<term_name>]
	 * : The term name.
	 *
	 * [<from_taxonomy>]
	 * : Taxonomy to migrate from.
	 *
	 * [<to_taxonomy>]
	 * : Taxonomy to migrate to.
	 *
	 * [--file=<file>]
	 * : Path to CSV file for batch merge.
	 *
	 * [--delete]
	 * : Delete the original terms after migrating.
	 *
	 * [--dry-run]
	 * : Simulate actions without making changes.
	 *
	 * [--log=<logfile>]
	 * : Path to a log file for results.
	 *
	 * ## EXAMPLES
	 *
	 *     wp boomi taxonomies migrate "Life Sciences" "blog_posts" "industries"
	 *     wp boomi taxonomies migrate "Life Sciences" "blog_posts" "industries" --delete
	 *     wp boomi taxonomies migrate --file=merge-terms.csv --dry-run --log=migrate.log
	 *
	 * @param string[]             $args       CLI positional arguments.
	 * @param array<string, mixed> $assoc_args CLI associative arguments.
	 *
	 * @return void
	 */
	public function migrate_terms( $args, $assoc_args ) {
		$dry_run         = isset( $assoc_args['dry-run'] );
		$delete_original = isset( $assoc_args['delete'] );
		$log_name        = $assoc_args['log'] ?? null;

		if ( $log_name ) {
			$this->set_log_name( $log_name );
		}

		// Batch merge.
		if ( isset( $assoc_args['file'] ) ) {
			if ( is_valid_file( $assoc_args['file'] ) ) {
				$this->process_csv( $assoc_args['file'], $delete_original, $dry_run );
			}

			$this->display_notices();

			return;
		}

		// Single command.
		$this->process_single_term( $args, $dry_run, $delete_original );

		$this->display_notices();
	}

	private function process_csv( string $file, bool $delete_original = false, bool $dry_run = false ) {
		$rows    = array_map( 'str_getcsv', file( $file ) );
		$headers = array_map( 'trim', array_shift( $rows ) );

		if ( ! $this->validate_headers( $headers, array( 'term_name', 'from_taxonomy', 'to_taxonomy' ) ) ) {
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
			if ( ! $this->has_required_fields( $data, array( 'term_name', 'from_taxonomy', 'to_taxonomy' ), $row_num ) ) {
				continue;
			}

			$term_name = $data['term_name'];
			$from_tax  = $data['from_taxonomy'];
			$to_tax    = $data['to_taxonomy'];

			$result = $this->migrate( $term_name, $from_tax, $to_tax, $delete_original, $row_num, $dry_run );

			if ( is_wp_error( $result ) ) {
				$this->add_notice( "Row $row_num: Error - " . $result->get_error_message(), 'warning' );
			}
		}

		$this->add_notice( $dry_run ? 'Dry run complete.' : 'Batch merge complete.', 'success' );
	}

	private function process_single_term( array $args, $dry_run, $delete_original ) {
		if ( ! $this->validate_command_args( $args, 3, 3 ) ) {
			$this->add_notice( 'Invalid arguments. Usage: wp taxonomy migrate <term_name> <from_taxonomy> <to_taxonomy>', 'error' );
			$this->log( 'Invalid arguments. Usage: wp taxonomy migrate <term_name> <from_taxonomy> <to_taxonomy>', 'error' );

			return;
		}

		list( $term_name, $from_tax, $to_tax ) = $args;

		$result = $this->migrate( $term_name, $from_tax, $to_tax, $delete_original, null, $dry_run );

		if ( is_wp_error( $result ) ) {
			$this->add_notice( $result->get_error_message(), 'error' );
		} else {
			$this->add_notice( "Migrated '$term_name' from '$from_tax' to '$to_tax'", 'success' );
		}
	}

	protected function migrate( string $term_name, string $from_tax, string $to_tax, bool $delete_original = false, $row_num = null, bool $dry_run = false ) {
		$prefix = $row_num ? "Row $row_num: " : '';

		$source_term = get_term_by( 'name', $term_name, $from_tax );

		if ( ! $source_term ) {
			$message = "{$prefix}Term '$term_name' not found in taxonomy '$from_tax'.";

			$this->add_notice( $message, 'error' );
			$this->log( $message, 'error' );

			return new WP_Error( 'term_not_found', $message );
		}

		// Check or create destination term.
		$dest_term = get_term_by( 'name', $term_name, $to_tax );

		if ( ! $dest_term ) {
			if ( $dry_run ) {
				$message = "{$prefix}Would create term '$term_name' in taxonomy '$to_tax'.";

				$this->add_notice( $message, 'success' );
				$this->log( $message, 'success' );

				$dest_term_id = 0; // placeholder.
			} else {
				$dest_term = wp_insert_term( $term_name, $to_tax );

				if ( is_wp_error( $dest_term ) ) {
					$message = "{$prefix}Failed to create term '$term_name' in taxonomy '$to_tax': " . $dest_term->get_error_message();
					$this->add_notice( $message, 'error' );
					$this->log( $message, 'error' );
					return $dest_term;
				}

				$dest_term_id = $dest_term['term_id'];
				$message      = "{$prefix}Created new term '$term_name' in taxonomy '$to_tax'.";

				$this->add_notice( $message, 'success' );
				$this->log( $message, 'success' );
			}
		} else {
			$dest_term_id = $dest_term->term_id;
		}

		// Get posts with the source term.
		$posts = get_posts(
			array(
				'post_type'      => 'any',
				'tax_query'      => array(
					array(
						'taxonomy' => $from_tax,
						'field'    => 'term_id',
						'terms'    => $source_term->term_id,
					),
				),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		if ( empty( $posts ) ) {
			$message = "{$prefix}No posts found with term '$term_name' in taxonomy '$from_tax'.";

			$this->add_notice( $message, 'warning' );
			$this->log( $message, 'warning' );

			return new WP_Error( 'no_posts_found', $message );
		}

		if ( $dry_run ) {
			$message = "{$prefix}Would update " . count( $posts ) . " post(s) with term '$term_name' in taxonomy '$to_tax'.";

			$this->add_notice( $message, 'success' );
			$this->log( $message, 'success' );
		} else {
			foreach ( $posts as $post_id ) {
				wp_set_object_terms( $post_id, $dest_term_id, $to_tax, true );
			}

			$message = "{$prefix}" . count( $posts ) . " post(s) updated with '$term_name' in taxonomy '$to_tax'.";

			$this->add_notice( $message, 'success' );
			$this->log( $message, 'success' );
		}

		// Delete term if requested.
		if ( $delete_original ) {
			if ( $dry_run ) {
				$message = "{$prefix}Would delete term '$term_name' from taxonomy '$from_tax'.";

				$this->add_notice( $message, 'success' );
				$this->log( $message, 'success' );
			} else {
				$result = wp_delete_term( $source_term->term_id, $from_tax );

				if ( is_wp_error( $result ) ) {
					$message = "{$prefix}Failed to delete term '$term_name' from taxonomy '$from_tax': " . $result->get_error_message();

					$this->add_notice( $message, 'error' );
					$this->log( $message, 'error' );
					return $result;
				} else {
					$message = "{$prefix}Deleted term '$term_name' from taxonomy '$from_tax'.";

					$this->add_notice( $message, 'success' );
					$this->log( $message, 'success' );
				}
			}
		}

		return true;
	}
}
