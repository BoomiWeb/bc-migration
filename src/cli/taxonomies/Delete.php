<?php
/**
 * Delete a Taxonomies Terms CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.2.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;

/**
 * Delete Class
 */
class Delete extends TaxonomyCLICommands {

	/**
	 * Delete a single term or bulk terms via a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * [<taxonomy> <term>]
	 * : Taxonomy and term to delete.
	 *
	 * [--file=<file>]
	 * : Path to CSV file for bulk delete.
	 *
	 * [--dry-run]
	 * : Only simulate the changes; no actual updates.
	 *
	 * [--log=<logfile>]
	 * : File to write logs to.
	 *
	 * ## EXAMPLES
	 *
	 *     wp boomi taxonomies delete industries "M&A" "Mergers. & Acquisitions"
	 *     wp boomi taxonomies delete --file=terms.csv --dry-run --log=delete-log.txt
	 *
	 * @param string[]             $args       CLI positional arguments.
	 * @param array<string, mixed> $assoc_args CLI associative arguments.
	 *
	 * @return void
	 */
	public function delete_terms( $args, $assoc_args ) {
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

		// Single command.
		$this->process_single_term( $args, $dry_run );

		$this->display_notices();
	}

	/**
	 * Process a CSV file of terms to delete.
	 *
	 * @param string $file The path to the CSV file.
	 * @param bool   $dry_run Whether to perform a dry run.
	 *
	 * @return void
	 */
	private function process_csv( string $file, bool $dry_run = false ) {
		$rows = array_map( function ( $line ) {
        	return str_getcsv( $line, ',', '"', '\\' );
    	}, file( $file ) );

		$headers = array_map( 'trim', array_shift( $rows ) );

		if ( ! $this->validate_headers( $headers, array( 'taxonomy', 'term' ) ) ) {
			return;
		}

		foreach ( $rows as $i => $row ) {
			$row_num    = $i + 2;
			$data       = array_combine( $headers, $row );
			$data       = array_map( 'trim', $data );
			$taxonomy   = $data['taxonomy'] ?? '';
			$term_names = explode( '|', $data['term'] );

			// skip empty lines.
			if ( count( $row ) === 1 && empty( trim( $row[0] ) ) ) {
				continue;
			}

			// Check required fields.
			if ( ! $this->has_required_fields( $data, array( 'taxonomy', 'term' ), $row_num ) ) {
				continue;
			}

			$taxonomy = $this->validate_taxonomy( $taxonomy );

			if ( is_wp_error( $taxonomy ) ) {
				$this->invalid_taxonomy( $taxonomy, $row_num );

				continue;
			}

			if ( $dry_run ) {
				$message = "Row $row_num: [DRY RUN] Would delete term(s) " . implode( ', ', $term_names ) . " in $taxonomy";

				$this->log( $message );

				$this->add_notice( $message );

				continue;
			}

			$this->delete_taxonomy_term( $taxonomy, $term_names, $row_num );
		}

		$this->add_notice( $dry_run ? 'Dry run complete.' : 'Batch merge complete.', 'success' );
	}

	/**
	 * Process a single term deletion.
	 *
	 * @param string[] $args   CLI arguments.
	 * @param bool     $dry_run  If set, no changes will be made.
	 *
	 * @return void
	 */
	private function process_single_term( array $args, $dry_run ) {
		$taxonomy   = $args[0] ?? '';
		$term_names = explode( '|', $args[1] ?? '' );

		if ( ! $this->validate_command_args( $args, 2, 2 ) ) {
			$this->add_notice( 'Invalid arguments. Usage: wp taxonomy delete_term <taxonomy> <term>', 'error' );

			return;
		}

		$taxonomy = $this->validate_taxonomy( $taxonomy );

		if ( is_wp_error( $taxonomy ) ) {
			$this->add_notice( $taxonomy->get_error_message(), 'error' );

			$this->log( "[SKIPPED] {$taxonomy->get_error_message()}" );

			return;
		}

		if ( $dry_run ) {
			$message = "[DRY RUN] Would delete term(s) '" . implode( ', ', $term_names ) . "' in taxonomy '$taxonomy'.";

			$this->log( $message );

			$this->add_notice( $message );

			return;
		}

		$this->delete_taxonomy_term( $taxonomy, $term_names );

		$this->display_notices();
	}

	/**
	 * Deletes specified taxonomy terms.
	 *
	 * @param string   $taxonomy  The taxonomy from which to delete terms.
	 * @param string[] $term_names An array of term names to be deleted.
	 * @param int|null $row_num  Optional row number for logging purposes.
	 *
	 * @return void
	 */
	private function delete_taxonomy_term( $taxonomy, $term_names, $row_num = null ) {
		foreach ( $term_names as $term_name ) {
			$term = $this->is_term_valid( $term_name, $taxonomy, $row_num );

			if ( ! $term ) {
				continue;
			}

			if ( ! is_wp_error( wp_delete_term( $term->term_id, $taxonomy ) ) ) {
				$this->log( ( $row_num ? "Row $row_num: " : '' ) . "Deleted term '$term_name'" );
				$this->add_notice( ( $row_num ? "Row $row_num: " : '' ) . "Deleted term '$term_name'", 'success' );
			} else {
				$this->log( ( $row_num ? "Row $row_num: " : '' ) . "Failed to delete term '$term_name'" );
				$this->add_notice( ( $row_num ? "Row $row_num: " : '' ) . "Failed to delete term '$term_name'", 'warning' );
			}
		}
	}
}
