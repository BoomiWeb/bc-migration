<?php
/**
 * Update Terms CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;

/**
 * UpdateTerms Class
 */
class UpdateTerms extends TaxonomyCLICommands {
	/**
	 * Updates or creates taxonomy terms with parent-child relationships.
	 *
	 * ## OPTIONS
	 *
	 * [<taxonomy>]
	 * : The taxonomy name.
	 *
	 * [<terms>]
	 * : A string defining parent > child relationships.
	 *
	 * [--file=<file>]
	 * : Path to a CSV file defining parent > children.
	 *
	 * [--dry-run]
	 * : If set, no changes will be made.
	 *
	 * [--log=<logfile>]
	 * : Path to a log file for results.
	 *
	 * ## EXAMPLES
	 *
	 *      wp boomi taxonomies update_terms content-type 'News & Updates > Press Release, News'
	 *      wp boomi taxonomies update_terms content-type 'News & Updates > Press Release, News' --log=update-terms.log
	 *      wp boomi taxonomies update_terms --file=terms.csv --dry-run
	 *      wp boomi taxonomies update_terms --file=path/to/file.csv --log=log.txt
	 *
	 * @when after_wp_load
	 *
	 * @param string[]             $args       CLI positional arguments.
	 * @param array<string, mixed> $assoc_args CLI associative arguments.
	 *
	 * @return void
	 */
	public function update_terms( $args, $assoc_args ) {
		$dry_run  = isset( $assoc_args['dry-run'] );
		$log_name = $assoc_args['log'] ?? null;

		if ( $log_name ) {
			$this->set_log_name( $log_name );
		}

		// Batch merge.
		if ( isset( $assoc_args['file'] ) ) {
			if ( is_valid_file( $assoc_args['file'] ) ) {
				$this->process_csv( $assoc_args['file'], $dry_run );
			}

			$this->display_notices();

			return;
		}

		// Single merge.
		$this->process_single_term( $args, $dry_run );

		$this->display_notices();
	}

	/**
	 * Processes a CSV file containing parent > children relationships and updates
	 * the terms in the specified taxonomy.
	 *
	 * @param string $file Path to the CSV file.
	 * @param bool   $dry_run  If set, no changes will be made.
	 *
	 * @return void
	 */
	private function process_csv( string $file, bool $dry_run ) {
		$rows    = array_map( 'str_getcsv', file( $file ) );
		$headers = array_map( 'trim', array_shift( $rows ) );

		if ( ! $this->validate_headers( $headers, array( 'taxonomy', 'terms' ) ) ) {
			return;
		}

		foreach ( $rows as $i => $row ) {
			$row_num  = $i + 2;
			$data     = array_combine( $headers, $row );
			$data     = array_map( 'trim', $data );
			$taxonomy = $data['taxonomy'];
			$mappings = array();

			// skip empty lines.
			if ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) {
				continue;
			}

			// Check required fields.
			if ( ! $this->has_required_fields( $data, array( 'taxonomy', 'terms' ), $row_num ) ) {
				continue;
			}

			$taxonomy = $this->validate_taxonomy( $taxonomy );

			if ( is_wp_error( $taxonomy ) ) {
				$this->invalid_taxonomy( $taxonomy, $row_num );

				continue;
			}

			$parts = explode( '>', $data['terms'] );

			if ( count( $parts ) !== 2 ) {
				$this->add_notice( "Invalid format in line: {$row_num}", 'warning' );
				$this->log( "Invalid format in line: {$row_num}" );

				continue;
			}

			$parent     = trim( $parts[0] );
			$children   = array_map( 'trim', explode( ',', $parts[1] ) );
			$mappings[] = array(
				'parent'   => $parent,
				'children' => $children,
			);

			if ( $dry_run ) {
				$this->add_notice( "Row $row_num: Dry run - No changes made.", 'info' );
				$this->log( "Row $row_num: Dry run - No changes made." );

				continue;
			}

			$result = $this->process_terms( $mappings, $taxonomy, $dry_run );

			if ( is_wp_error( $result ) ) {
				$this->add_notice( "Row $row_num: Error - " . $result->get_error_message(), 'warning' );
			}
		}
	}

	private function process_single_term( array $args, bool $dry_run ) {
		$taxonomy = $this->validate_taxonomy( $args[0] );

		if ( is_wp_error( $taxonomy ) ) {
			$this->add_notice( $taxonomy->get_error_message(), 'error' );

			return;
		}

		if ( ! $this->validate_command_args( $args, 2, 2 ) ) {
			$this->add_notice( 'Invalid arguments. Usage: wp taxonomy update_terms <taxonomy> <terms>', 'error' );

			return;
		}

		$input = $args[1];
		$parts = explode( '>', $input );

		if ( count( $parts ) === 2 ) {
			$parent     = trim( $parts[0] );
			$children   = array_map( 'trim', explode( ',', $parts[1] ) );
			$mappings[] = array(
				'parent'   => $parent,
				'children' => $children,
			);
		} else {
			$this->add_notice( 'Invalid input format. Use: Parent > Child1, Child2', 'error' );
			$this->log( 'Invalid input format. Use: Parent > Child1, Child2' );

			return;
		}

		if ( empty( $mappings ) ) {
			$this->add_notice( 'No valid mappings found in input.', 'error' );
			$this->log( 'No valid mappings found in input.' );

			return;
		}

		$this->process_terms( $mappings, $taxonomy, $dry_run );
	}

	/**
	 * Process a set of terms with parent-child relationships.
	 *
	 * @param array<array{parent: string, children: string[]}> $mappings Set of term sets with parent and children.
	 * @param string                                           $taxonomy The taxonomy to update.
	 * @param bool                                             $dry_run If set, no changes will be made.
	 *
	 * @return void
	 */
	private function process_terms( $mappings, $taxonomy, $dry_run ) {
		foreach ( $mappings as $set ) {
			$parent   = $set['parent'];
			$children = $set['children'];

			$parent_term = term_exists( $parent, $taxonomy );

			if ( ! $parent_term ) {
				if ( $dry_run ) {
					$this->add_notice( "Parent term does not exist: {$parent}", 'warning' );
					$this->log( "Parent term does not exist: {$parent}" );

					$parent_id = 0;
				} else {
					$result = wp_insert_term( $parent, $taxonomy );

					if ( is_wp_error( $result ) ) {
						$this->add_notice( "Failed to create parent term '{$parent}': " . $result->get_error_message(), 'warning' );
						$this->log( "Failed to create parent term '{$parent}': " . $result->get_error_message() );

						continue;
					}

					$parent_id = $result['term_id'];

					$this->add_notice( "Created parent term: {$parent}", 'success' );
					$this->log( "Created parent term: {$parent}" );
				}
			} else {
				$parent_id = is_array( $parent_term ) ? $parent_term['term_id'] : $parent_term;

				$this->add_notice( "Parent term exists: {$parent} (ID {$parent_id})", 'success' );
				$this->log( "Parent term exists: {$parent} (ID {$parent_id})" );
			}

			foreach ( $children as $child ) {
				$child_term = term_exists( $child, $taxonomy );

				if ( ! $child_term ) {
					if ( $dry_run ) {
						$this->add_notice( "Child term does not exist: {$child}", 'warning' );
						$this->log( "Child term does not exist: {$child}" );
					} else {
						$result = wp_insert_term( $child, $taxonomy, array( 'parent' => $parent_id ) );

						if ( is_wp_error( $result ) ) {
							$this->add_notice( "Failed to create child term '{$child}': " . $result->get_error_message(), 'warning' );
							$this->log( "Failed to create child term '{$child}': " . $result->get_error_message() );
						} else {
							$this->add_notice( "Created child term: {$child} under {$parent}", 'success' );
							$this->log( "Created child term: {$child} under {$parent}" );
						}
					}
				} else {
					$child_id = is_array( $child_term ) ? $child_term['term_id'] : $child_term;

					if ( $dry_run ) {
						$this->add_notice( "Child term exists: {$child} (ID {$child_id})", 'success' );
						$this->log( "Child term exists: {$child} (ID {$child_id})" );
					} else {
						wp_update_term( (int) $child_id, $taxonomy, array( 'parent' => $parent_id ) );

						$this->add_notice( "Updated child term: {$child} to be under {$parent}", 'success' );
						$this->log( "Updated child term: {$child} to be under {$parent}" );
					}
				}
			}
		}
	}
}
