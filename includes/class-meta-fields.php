<?php
/**
 * Meta Fields for Quick Choices
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

    /**
     * Instance of this class
     *
     * @var QCM_Meta_Fields
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return QCM_Meta_Fields
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
        add_action( 'init', array( $this, 'register_meta_fields' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta_box' ) ); // AÑADE ESTA LÍNEA
    }

    /**
     * Add meta box
     */
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

    /**
     * Render meta box
     */
    public function render_meta_box( $post ) {
        echo '<div id="qcm-meta-fields-root"></div>';
    }

    /**
     * Save meta box data
     */
    public function save_meta_box( $post_id ) {
        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Check post type
        if ( 'quick_choice' !== get_post_type( $post_id ) ) {
            return;
        }

        // Meta is saved via REST API from React component
        // This is just a placeholder to ensure the meta box is recognized
    }

    /**
     * Register meta fields
     */
    public function register_meta_fields() {
        // Choice items (array of objects with image and title)
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

        // API configuration
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

        // Last API search query
        register_post_meta(
            'quick_choice',
            'qcm_last_search',
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

    /**
     * Enqueue editor assets
     */
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

        // Pass data to JavaScript
        wp_localize_script(
            'qcm-meta-fields',
            'qcmMetaFields',
            array(
                'pluginUrl' => QCM_PLUGIN_URL,
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'qcm_meta_fields' ),
            )
        );
    }
}
