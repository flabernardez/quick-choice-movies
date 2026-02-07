<?php
/**
 * Meta Fields for Quick Choices
 * WORKING VERSION - Uses direct database save
 *
 * @package QuickChoiceMovies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QCM_Meta_Fields
 */
class QCM_Meta_Fields {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_meta_fields' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

        // Add AJAX handler for saving
        add_action( 'wp_ajax_qcm_save_items', array( $this, 'ajax_save_items' ) );
    }

    public function add_meta_box() {
        add_meta_box(
            'qcm-choice-items',
            __( 'Quick Choice Items', 'quick-choice-movies' ),
            array( $this, 'render_meta_box' ),
            'quick_choice',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'qcm_save_items', 'qcm_items_nonce' );
        echo '<div id="qcm-meta-fields-root"></div>';
    }

    /**
     * AJAX handler to save items
     */
    public function ajax_save_items() {
        check_ajax_referer( 'qcm_save_items', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        // CRITICAL: Use wp_unslash to get the raw JSON
        $items = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '';

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => 'Invalid post ID' ) );
        }

        if ( empty( $items ) ) {
            wp_send_json_error( array( 'message' => 'No items provided' ) );
        }

        // Validate JSON
        $decoded = json_decode( $items, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( array(
                'message' => 'Invalid JSON: ' . json_last_error_msg(),
                'received' => substr( $items, 0, 100 )
            ) );
        }

        // Save to database - don't use sanitize_text_field on JSON!
        $result = update_post_meta( $post_id, 'qcm_choice_items', $items );

        // Verify it was saved
        $saved = get_post_meta( $post_id, 'qcm_choice_items', true );

        if ( $saved === $items ) {
            wp_send_json_success( array(
                'message' => 'Saved successfully',
                'count' => count( $decoded ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Save verification failed' ) );
        }
    }

    public function register_meta_fields() {
        register_post_meta(
            'quick_choice',
            'qcm_choice_items',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'default'      => '',
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                }
            )
        );

        register_post_meta(
            'quick_choice',
            'qcm_api_source',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'default'      => '',
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                }
            )
        );
    }

    public function enqueue_editor_assets() {
        $screen = get_current_screen();

        if ( ! $screen || 'quick_choice' !== $screen->post_type ) {
            return;
        }

        $asset_file = include( QCM_PLUGIN_DIR . 'build/meta-fields/index.asset.php' );

        wp_enqueue_script(
            'qcm-meta-fields',
            QCM_PLUGIN_URL . 'build/meta-fields/index.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        wp_enqueue_style(
            'qcm-meta-fields',
            QCM_PLUGIN_URL . 'build/meta-fields/index.css',
            array( 'wp-components' ),
            $asset_file['version']
        );

        global $post;
        $post_id = $post ? $post->ID : 0;
        $current_meta = get_post_meta( $post_id, 'qcm_choice_items', true );

        wp_localize_script(
            'qcm-meta-fields',
            'qcmMetaFields',
            array(
                'pluginUrl'      => QCM_PLUGIN_URL,
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( 'qcm_save_items' ),
                'searchNonce'    => wp_create_nonce( 'qcm_api_search' ),
                'postId'         => $post_id,
                'currentMeta'    => $current_meta,
            )
        );
    }
}
