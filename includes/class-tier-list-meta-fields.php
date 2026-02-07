<?php
/**
 * Meta Fields for Tier Lists
 *
 * @package QuickChoiceMovies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QCM_Tier_List_Meta_Fields
 */
class QCM_Tier_List_Meta_Fields {

    private static $instance = null;

    /**
     * Default tiers configuration
     */
    const DEFAULT_TIERS = array(
        array( 'id' => 'S', 'label' => 'S', 'color' => '#ff7f7f' ),
        array( 'id' => 'A', 'label' => 'A', 'color' => '#ffbf7f' ),
        array( 'id' => 'B', 'label' => 'B', 'color' => '#ffdf7f' ),
        array( 'id' => 'C', 'label' => 'C', 'color' => '#ffff7f' ),
        array( 'id' => 'D', 'label' => 'D', 'color' => '#bfff7f' ),
        array( 'id' => 'F', 'label' => 'F', 'color' => '#7fbfff' ),
    );

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
        add_action( 'wp_ajax_qcm_save_tier_list', array( $this, 'ajax_save_tier_list' ) );
    }

    public function add_meta_box() {
        add_meta_box(
            'qcm-tier-list-items',
            __( 'Tier List Configuration', 'quick-choice-movies' ),
            array( $this, 'render_meta_box' ),
            'tier_list',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'qcm_save_tier_list', 'qcm_tier_list_nonce' );
        echo '<div id="qcm-tier-list-meta-root"></div>';
    }

    /**
     * AJAX handler to save tier list
     */
    public function ajax_save_tier_list() {
        check_ajax_referer( 'qcm_save_tier_list', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $items = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '';
        $tiers = isset( $_POST['tiers'] ) ? wp_unslash( $_POST['tiers'] ) : '';

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => 'Invalid post ID' ) );
        }

        // Validate JSON
        $decoded_items = json_decode( $items, true );
        $decoded_tiers = json_decode( $tiers, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( array( 'message' => 'Invalid JSON: ' . json_last_error_msg() ) );
        }

        // Save to database
        update_post_meta( $post_id, 'qcm_tier_list_items', $items );
        update_post_meta( $post_id, 'qcm_tier_list_tiers', $tiers );

        wp_send_json_success( array(
            'message' => 'Saved successfully',
            'items_count' => count( $decoded_items ),
            'tiers_count' => count( $decoded_tiers ),
        ) );
    }

    public function register_meta_fields() {
        register_post_meta(
            'tier_list',
            'qcm_tier_list_items',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'default'      => '[]',
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                }
            )
        );

        register_post_meta(
            'tier_list',
            'qcm_tier_list_tiers',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'default'      => json_encode( self::DEFAULT_TIERS ),
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                }
            )
        );
    }

    public function enqueue_editor_assets() {
        $screen = get_current_screen();

        if ( ! $screen || 'tier_list' !== $screen->post_type ) {
            return;
        }

        $asset_path = QCM_PLUGIN_DIR . 'build/meta-fields/index.asset.php';
        if ( ! file_exists( $asset_path ) ) {
            return;
        }
        $asset_file = include( $asset_path );

        wp_enqueue_script(
            'qcm-tier-list-meta-fields',
            QCM_PLUGIN_URL . 'build/meta-fields/index.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        wp_enqueue_style(
            'qcm-tier-list-meta-fields',
            QCM_PLUGIN_URL . 'build/meta-fields/index.css',
            array( 'wp-components' ),
            $asset_file['version']
        );

        global $post;
        $post_id = $post ? $post->ID : 0;
        $current_items = get_post_meta( $post_id, 'qcm_tier_list_items', true );
        $current_tiers = get_post_meta( $post_id, 'qcm_tier_list_tiers', true );

        if ( empty( $current_tiers ) ) {
            $current_tiers = json_encode( self::DEFAULT_TIERS );
        }

        wp_localize_script(
            'qcm-tier-list-meta-fields',
            'qcmTierListMeta',
            array(
                'pluginUrl'    => QCM_PLUGIN_URL,
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'qcm_save_tier_list' ),
                'searchNonce'  => wp_create_nonce( 'qcm_api_search' ),
                'postId'       => $post_id,
                'currentItems' => $current_items,
                'currentTiers' => $current_tiers,
                'defaultTiers' => self::DEFAULT_TIERS,
            )
        );
    }
}
