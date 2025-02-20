<?php
/**
 * Shortcode search background process.
 *
 * @package BoomiCMS\BackgroundProcesses
 * @since   4.3.8
 * @version 0.1.0
 */

namespace BoomiCMS\BackgroundProcesses;

use BoomiCMS\Traits\Write_To_File;

/**
 * BC_Shortcode_Search_Process class
 */
class BC_Shortcode_Search_Process extends \WP_Background_Process {

    use Write_To_File;

    /**
     * Action.
     *
     * @var string
     */
    protected $action = 'shortcode_search_process';

    /**
     * Gets the posts for the post type. Then checks for the shortcode.
     *
     * @see WP_Background_Process::task()
     * @param array $data The data for the task.
     * @return bool False to perform the next task.
     */
    protected function task( $data ) {
        $posts = get_posts(
            array(
                'posts_per_page' => -1,
                'post_type'      => $data['post_type'],
            )
        );

        $posts_have_shortcode = $this->check_for_shortcode( $posts, $data['shortcode'] );

        if ( ! empty( $posts_have_shortcode ) ) {
            $this->write_to_file( $posts_have_shortcode, $data['post_type'] );
        }

        return false;
    }

    /**
     * Check the post content for a shortcode.
     *
     * @param array  $posts The array of posts.
     * @param string $shortcode The shortcode.
     * @return array|false The array of posts with the shortcode or false.
     */
    public function check_for_shortcode( $posts = array(), $shortcode = '' ) {
        $have_shortcode = array();

        if ( empty( $posts ) || empty( $shortcode ) ) {
            return false;
        }

        foreach ( $posts as $post ) {
            if ( ! isset( $post->post_content ) || empty( $post->post_content ) ) {
                if ( ! function_exists( 'get_fields' ) ) {
                    continue;
                } else {
                    $fields        = get_fields( $post->ID, false );
                    $has_shortcode = boomi_array_has_shortcode( $shortcode, $fields );

                    if ( true === $has_shortcode ) {
                        $have_shortcode[] = $post->ID;
                    }
                }
            } elseif ( isset( $post->post_content ) && has_shortcode( $post->post_content, $shortcode ) ) {
                $have_shortcode[] = $post->ID; // @phpstan-ignore-line
            }
        }

        return $have_shortcode;
    }

    /**
     * Writes the given posts and post type to a file.
     *
     * @param array  $posts The array of posts to write.
     * @param string $post_type The post type to write.
     * @return void
     */
    protected function write_to_file( $posts = array(), $post_type = '' ) {
        $data = array(
            'posts'     => $posts,
            'post_type' => $post_type,
        );

        $upload_dir      = wp_upload_dir();
        $upload_dir_path = $upload_dir['path'] . '/_bc_tmp/';

        $this->write_json_to_file( $data, 'bc_shortcode_search', $upload_dir_path );
    }

    /**
     * Complete
     *
     * @see WP_Background_Process::complete()
     * @return void
     */
    protected function complete() {
        parent::complete();

        do_action( 'bc_shortcode_search_process_complete' );
    }
}
