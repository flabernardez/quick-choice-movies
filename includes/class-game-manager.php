<?php
/**
 * Game Manager
 *
 * @package QuickChoiceMovies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QCM_Game_Manager
 */
class QCM_Game_Manager {

    /**
     * Instance of this class
     *
     * @var QCM_Game_Manager
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return QCM_Game_Manager
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'wp_ajax_qcm_get_choices', array( $this, 'ajax_get_choices' ) );
        add_action( 'wp_ajax_nopriv_qcm_get_choices', array( $this, 'ajax_get_choices' ) );
    }

    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        if ( ! $this->should_load_assets() ) {
            return;
        }

        wp_enqueue_script(
            'qcm-game',
            QCM_PLUGIN_URL . 'public/js/game.js',
            array( 'jquery' ),
            QCM_VERSION,
            true
        );

        wp_enqueue_style(
            'qcm-game',
            QCM_PLUGIN_URL . 'public/css/game.css',
            array(),
            QCM_VERSION
        );

        wp_localize_script(
            'qcm-game',
            'qcmGame',
            array(
                'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
                'nonce'            => wp_create_nonce( 'qcm_game' ),
                'cookieExpiration' => get_option( 'qcm_cookie_expiration', 30 ),
            )
        );
    }

    /**
     * Check if assets should be loaded
     *
     * @return bool
     */
    private function should_load_assets() {
        // Always load on quick_choice post type pages
        if ( is_singular( 'quick_choice' ) ) {
            return true;
        }

        // Check if the qcm-game block is present in the content
        if ( is_singular() ) {
            global $post;
            if ( $post && has_block( 'qcm/game-block', $post ) ) {
                return true;
            }
        }

        // Load on all pages and posts as fallback (for testing)
        return is_singular();
    }

    /**
     * AJAX handler to get choices from a post
     */
    public function ajax_get_choices() {
        check_ajax_referer( 'qcm_game', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'quick-choice-movies' ) ) );
        }

        $choice_items = get_post_meta( $post_id, 'qcm_choice_items', true );

        if ( ! $choice_items ) {
            wp_send_json_error( array( 'message' => __( 'No choices found', 'quick-choice-movies' ) ) );
        }

        $items = json_decode( $choice_items, true );

        if ( ! is_array( $items ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid choices data', 'quick-choice-movies' ) ) );
        }

        wp_send_json_success( array( 'items' => $items ) );
    }
}
