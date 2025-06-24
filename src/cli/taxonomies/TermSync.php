<?php
/**
 * Term Sync CLI class
 *
 * @package erikdmitchell\bcmigration\cli\taxonomies
 * @since   0.3.3
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration\cli\taxonomies;

use erikdmitchell\bcmigration\abstracts\TaxonomyCLICommands;
use WP_Error;
use WP_CLI;

/**
 * TermSync class.
 */
class TermSync extends TaxonomyCLICommands {

    /**
     * Match and sync terms between taxonomies
     *
     * ## OPTIONS
     *
     * --source=<taxonomy>
     * : Source taxonomy to match terms from
     *
     * --target=<taxonomies>
     * : Target taxonomy/taxonomies to sync to (comma-separated for multiple)
     *
     * --post-type=<post_type>
     * : Post type to process
     *
     * [--term=<term_name>]
     * : Specific term to match (optional, if not provided will match all terms)
     *
     * [--dry-run]
     * : Show what would be done without making changes
     *
     * [--batch-size=<number>]
     * : Number of posts to process at once (default: 100)
     *
     * ## EXAMPLES
     *
     *     wp boomi taxonomies term-sync match --source=blog_posts --target=products --post-type=post
     *     wp boomi taxonomies term-sync match --source=blog_posts --target=products,topics --post-type=custom_post --term="API Management"
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function match($args, $assoc_args) {
        $source_taxonomy = $assoc_args['source'];
        $target_taxonomies = explode(',', $assoc_args['target']);
        $post_type = $assoc_args['post-type'];
        $specific_term = isset($assoc_args['term']) ? $assoc_args['term'] : null;
        $dry_run = isset($assoc_args['dry-run']);
        $batch_size = isset($assoc_args['batch-size']) ? intval($assoc_args['batch-size']) : 100;

        // Validate taxonomies.
        if (!taxonomy_exists($source_taxonomy)) {
            WP_CLI::error("Source taxonomy '{$source_taxonomy}' does not exist.");
        }

        foreach ($target_taxonomies as $target_taxonomy) {
            $target_taxonomy = trim($target_taxonomy);
            if (!taxonomy_exists($target_taxonomy)) {
                WP_CLI::error("Target taxonomy '{$target_taxonomy}' does not exist.");
            }
        }

        // Validate post type.
        if (!post_type_exists($post_type)) {
            WP_CLI::error("Post type '{$post_type}' does not exist.");
        }

        WP_CLI::log("Starting taxonomy sync...");
        WP_CLI::log("Source: {$source_taxonomy}");
        WP_CLI::log("Target(s): " . implode(', ', $target_taxonomies));
        WP_CLI::log("Post Type: {$post_type}");
        
        if ($specific_term) {
            WP_CLI::log("Specific Term: {$specific_term}");
        }
        
        if ($dry_run) {
            WP_CLI::log("DRY RUN MODE - No changes will be made");
        }

        $processed = 0;
        $updated = 0;
        $errors = 0;

        // Get terms to process.
        $terms_to_process = $this->get_terms_to_process($source_taxonomy, $specific_term);
        
        if (empty($terms_to_process)) {
            WP_CLI::warning("No terms found to process.");
            return;
        }

        WP_CLI::log("Found " . count($terms_to_process) . " terms to process.");

        foreach ($terms_to_process as $source_term) {
            WP_CLI::log("Processing term: {$source_term->name}");
            
            // Get posts tagged with this term in source taxonomy.
            $posts = $this->get_posts_with_term($post_type, $source_taxonomy, $source_term->term_id, $batch_size);
            
            if (empty($posts)) {
                WP_CLI::log("  No posts found with this term.");
                continue;
            }

            WP_CLI::log("  Found " . count($posts) . " posts with this term.");

            foreach ($target_taxonomies as $target_taxonomy) {
                $target_taxonomy = trim($target_taxonomy);
                
                // Find existing term in target taxonomy (do not create new terms).
                $target_term = $this->find_existing_term($source_term->name, $target_taxonomy);
                
                if (!$target_term) {
                    WP_CLI::log("  Term '{$source_term->name}' does not exist in taxonomy '{$target_taxonomy}' - skipping");
                    continue;
                }

                WP_CLI::log("  Found matching term '{$target_term['name']}' in taxonomy '{$target_taxonomy}'");

                // Add term to posts.
                foreach ($posts as $post) {
                    $result = $this->add_term_to_post($post->ID, $target_term['term_id'], $target_taxonomy, $dry_run);
                    
                    if ($result['success']) {
                        if ($result['added']) {
                            WP_CLI::log("    Added term '{$target_term['name']}' to post ID {$post->ID} in taxonomy '{$target_taxonomy}'");
                            $updated++;
                        }
                    } else {
                        WP_CLI::warning("    Failed to add term to post ID {$post->ID}: {$result['message']}");
                        $errors++;
                    }
                    
                    $processed++;
                }
            }
        }

        WP_CLI::success("Processing complete!");
        WP_CLI::log("Posts processed: {$processed}");
        WP_CLI::log("Terms added: {$updated}");
        if ($errors > 0) {
            WP_CLI::log("Errors: {$errors}");
        }
    }

    /**
     * Bulk sync terms using CSV file
     *
     * ## OPTIONS
     *
     * --csv-file=<path>
     * : Path to CSV file with columns: post_id, source_taxonomy, target_taxonomy, term_name
     *
     * --post-type=<post_type>
     * : Post type to process
     *
     * [--dry-run]
     * : Show what would be done without making changes
     *
     * ## EXAMPLES
     *
     *     wp boomi taxonomies term-sync bulk --csv-file=sync.csv --post-type=post
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function bulk($args, $assoc_args) {
        $csv_file = $assoc_args['csv-file'];
        $post_type = $assoc_args['post-type'];
        $dry_run = isset($assoc_args['dry-run']);

        if (!file_exists($csv_file)) {
            WP_CLI::error("CSV file '{$csv_file}' does not exist.");
        }

        if (!post_type_exists($post_type)) {
            WP_CLI::error("Post type '{$post_type}' does not exist.");
        }

        WP_CLI::log("Starting bulk taxonomy sync from CSV...");
        WP_CLI::log("CSV File: {$csv_file}");
        WP_CLI::log("Post Type: {$post_type}");
        
        if ($dry_run) {
            WP_CLI::log("DRY RUN MODE - No changes will be made");
        }

        $processed = 0;
        $updated = 0;
        $errors = 0;

        if (($handle = fopen($csv_file, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            
            // Validate CSV headers.
            $required_headers = ['post_id', 'source_taxonomy', 'target_taxonomy', 'term_name'];
            foreach ($required_headers as $required_header) {
                if (!in_array($required_header, $header)) {
                    WP_CLI::error("CSV file must contain column: {$required_header}");
                }
            }

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row = array_combine($header, $data);
                
                $post_id = intval($row['post_id']);
                $source_taxonomy = trim($row['source_taxonomy']);
                $target_taxonomy = trim($row['target_taxonomy']);
                $term_name = trim($row['term_name']);

                WP_CLI::log("Processing: Post {$post_id}, Term '{$term_name}', {$source_taxonomy} -> {$target_taxonomy}");

                // Validate post exists and is correct type.
                $post = get_post($post_id);
                if (!$post || $post->post_type !== $post_type) {
                    WP_CLI::warning("  Post {$post_id} not found or wrong post type");
                    $errors++;
                    continue;
                }

                // Validate taxonomies.
                if (!taxonomy_exists($source_taxonomy) || !taxonomy_exists($target_taxonomy)) {
                    WP_CLI::warning("  Invalid taxonomy");
                    $errors++;
                    continue;
                }

                // Check if post has the source term.
                if (!has_term($term_name, $source_taxonomy, $post_id)) {
                    WP_CLI::warning("  Post {$post_id} does not have term '{$term_name}' in taxonomy '{$source_taxonomy}'");
                    $errors++;
                    continue;
                }

                // Find existing term in target taxonomy (do not create new terms).
                $target_term = $this->find_existing_term($term_name, $target_taxonomy);
                
                if (!$target_term) {
                    WP_CLI::warning("  Term '{$term_name}' does not exist in taxonomy '{$target_taxonomy}' - skipping");
                    $errors++;
                    continue;
                }

                // Add term to post.
                $result = $this->add_term_to_post($post_id, $target_term['term_id'], $target_taxonomy, $dry_run);
                
                if ($result['success']) {
                    if ($result['added']) {
                        WP_CLI::log("  Added term '{$target_term['name']}' to post {$post_id}");
                        $updated++;
                    } else {
                        WP_CLI::log("  Term already exists on post {$post_id}");
                    }
                } else {
                    WP_CLI::warning("  Failed to add term: {$result['message']}");
                    $errors++;
                }
                
                $processed++;
            }
            fclose($handle);
        }

        WP_CLI::success("Bulk processing complete!");
        WP_CLI::log("Rows processed: {$processed}");
        WP_CLI::log("Terms added: {$updated}");
        if ($errors > 0) {
            WP_CLI::log("Errors: {$errors}");
        }
    }

    /**
     * List terms in a taxonomy for reference
     *
     * ## OPTIONS
     *
     * --taxonomy=<taxonomy>
     * : Taxonomy to list terms from
     *
     * [--search=<term>]
     * : Search for specific term
     *
     * ## EXAMPLES
     *
     *     wp boomi taxonomies term-sync list-terms --taxonomy=blog_posts
     *     wp boomi taxonomies term-sync list-terms --taxonomy=products --search="API"
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function list_terms($args, $assoc_args) {
        $taxonomy = $assoc_args['taxonomy'];
        $search = isset($assoc_args['search']) ? $assoc_args['search'] : '';

        if (!taxonomy_exists($taxonomy)) {
            WP_CLI::error("Taxonomy '{$taxonomy}' does not exist.");
        }

        $args = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        );

        if ($search) {
            $args['search'] = $search;
        }

        $terms = get_terms($args);

        if (empty($terms)) {
            WP_CLI::log("No terms found in taxonomy '{$taxonomy}'");
            return;
        }

        WP_CLI::log("Terms in taxonomy '{$taxonomy}':");
        foreach ($terms as $term) {
            WP_CLI::log("  {$term->name} (ID: {$term->term_id}, Count: {$term->count})");
        }
    }
    

    private function get_terms_to_process($taxonomy, $specific_term = null) {
        $args = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        );

        if ($specific_term) {
            $args['name'] = $specific_term;
        }

        return get_terms($args);
    }


    private function get_posts_with_term($post_type, $taxonomy, $term_id, $batch_size) {
        return get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'numberposts' => $batch_size,
            'tax_query' => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            ),
        ));
    }

    private function find_existing_term($term_name, $taxonomy) {
        // Only find existing terms, do not create new ones.
        $existing_term = get_term_by('name', $term_name, $taxonomy);
        
        if ($existing_term) {
            return array(
                'term_id' => $existing_term->term_id,
                'name' => $existing_term->name,
                'exists' => true
            );
        }

        return false;
    }

    private function add_term_to_post($post_id, $term_id, $taxonomy, $dry_run = false) {
        // Check if post already has this term.
        if (has_term($term_id, $taxonomy, $post_id)) {
            return array(
                'success' => true,
                'added' => false,
                'message' => 'Term already exists on post'
            );
        }

        if ($dry_run) {
            return array(
                'success' => true,
                'added' => true,
                'message' => 'Would add term (dry run)'
            );
        }

        // Add term to post (append, don't replace).
        $result = wp_set_object_terms($post_id, $term_id, $taxonomy, true);

        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'added' => false,
                'message' => $result->get_error_message()
            );
        }

        return array(
            'success' => true,
            'added' => true,
            'message' => 'Term added successfully'
        );
    }
}