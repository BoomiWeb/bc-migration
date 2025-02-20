<?php
/**
 * Shortcode search background process.
 *
 * @package erikdmitchell\bcmigration\BackgroundProcesses
 * @since 0.1.0
 * @version 0.1.0
 */

namespace erikdmitchell\bcmigration;

/**
 * ShortcodSearchProcess class
 */
class ShortcodSearchProcess extends WP_Background_Process {

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
            $this->write_to_file( $posts_have_shortcode, $data['post_type'] ); // needs to return the posts with the shortcode
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
                    $has_shortcode = $this->array_has_shortcode( $shortcode, $fields );

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

    private function array_has_shortcode( $shortcode = '', $arr = null ) {
        if ( ! is_array( $arr ) ) {
            return false;
        }
    
        foreach ( $arr as $key => $value ) {
            if ( is_array( $value ) ) {
                $return = $this->array_has_shortcode( $shortcode, $value );
    
                if ( $return ) {
                    return true;
                }
            } elseif ( null === $value ) {
                return false;
            } elseif ( has_shortcode( $value, $shortcode ) ) {
                    return true;
            }
        }
    
        return false;
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
