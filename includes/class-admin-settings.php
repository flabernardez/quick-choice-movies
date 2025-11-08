<?php
/**
 * Admin Settings Page
 *
 * @package QuickChoiceMovies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QCM_Admin_Settings
 */
class QCM_Admin_Settings {

    /**
     * Instance of this class
     *
     * @var QCM_Admin_Settings
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return QCM_Admin_Settings
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
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=quick_choice',
            __( 'Settings', 'quick-choice-movies' ),
            __( 'Settings', 'quick-choice-movies' ),
            'manage_options',
            'qcm-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings Section
        add_settings_section(
            'qcm_api_settings',
            __( 'API Settings', 'quick-choice-movies' ),
            array( $this, 'api_settings_callback' ),
            'qcm-settings'
        );

        // TMDB API Key
        register_setting( 'qcm_settings', 'qcm_tmdb_api_key' );
        add_settings_field(
            'qcm_tmdb_api_key',
            __( 'TMDB API Key', 'quick-choice-movies' ),
            array( $this, 'tmdb_api_key_callback' ),
            'qcm-settings',
            'qcm_api_settings'
        );

        // RAWG API Key (for video games)
        register_setting( 'qcm_settings', 'qcm_rawg_api_key' );
        add_settings_field(
            'qcm_rawg_api_key',
            __( 'RAWG API Key (Video Games)', 'quick-choice-movies' ),
            array( $this, 'rawg_api_key_callback' ),
            'qcm-settings',
            'qcm_api_settings'
        );

        // Google Books API Key
        register_setting( 'qcm_settings', 'qcm_google_books_api_key' );
        add_settings_field(
            'qcm_google_books_api_key',
            __( 'Google Books API Key', 'quick-choice-movies' ),
            array( $this, 'google_books_api_key_callback' ),
            'qcm-settings',
            'qcm_api_settings'
        );

        // Game Settings Section
        add_settings_section(
            'qcm_game_settings',
            __( 'Game Settings', 'quick-choice-movies' ),
            array( $this, 'game_settings_callback' ),
            'qcm-settings'
        );

        // Cookie expiration
        register_setting( 'qcm_settings', 'qcm_cookie_expiration', array(
            'default' => 30
        ) );
        add_settings_field(
            'qcm_cookie_expiration',
            __( 'Cookie Expiration (days)', 'quick-choice-movies' ),
            array( $this, 'cookie_expiration_callback' ),
            'qcm-settings',
            'qcm_game_settings'
        );
    }

    /**
     * API settings section callback
     */
    public function api_settings_callback() {
        echo '<p>' . esc_html__( 'Configure API keys for searching movies, video games, and books.', 'quick-choice-movies' ) . '</p>';
    }

    /**
     * Game settings section callback
     */
    public function game_settings_callback() {
        echo '<p>' . esc_html__( 'Configure general game settings.', 'quick-choice-movies' ) . '</p>';
    }

    /**
     * TMDB API Key field callback
     */
    public function tmdb_api_key_callback() {
        $value = get_option( 'qcm_tmdb_api_key', '' );
        echo '<input type="text" name="qcm_tmdb_api_key" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . sprintf(
                __( 'Get your API key from <a href="%s" target="_blank">TMDB</a>', 'quick-choice-movies' ),
                'https://www.themoviedb.org/settings/api'
            ) . '</p>';
    }

    /**
     * RAWG API Key field callback
     */
    public function rawg_api_key_callback() {
        $value = get_option( 'qcm_rawg_api_key', '' );
        echo '<input type="text" name="qcm_rawg_api_key" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . sprintf(
                __( 'Get your API key from <a href="%s" target="_blank">RAWG</a>', 'quick-choice-movies' ),
                'https://rawg.io/apidocs'
            ) . '</p>';
    }

    /**
     * Google Books API Key field callback
     */
    public function google_books_api_key_callback() {
        $value = get_option( 'qcm_google_books_api_key', '' );
        echo '<input type="text" name="qcm_google_books_api_key" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . sprintf(
                __( 'Get your API key from <a href="%s" target="_blank">Google Cloud Console</a>', 'quick-choice-movies' ),
                'https://console.cloud.google.com/apis/credentials'
            ) . '</p>';
    }

    /**
     * Cookie expiration field callback
     */
    public function cookie_expiration_callback() {
        $value = get_option( 'qcm_cookie_expiration', 30 );
        echo '<input type="number" name="qcm_cookie_expiration" value="' . esc_attr( $value ) . '" min="1" max="365" />';
        echo '<p class="description">' . esc_html__( 'Number of days before game progress cookie expires.', 'quick-choice-movies' ) . '</p>';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Show success message if settings saved
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error(
                'qcm_messages',
                'qcm_message',
                __( 'Settings saved successfully.', 'quick-choice-movies' ),
                'updated'
            );
        }

        settings_errors( 'qcm_messages' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'qcm_settings' );
                do_settings_sections( 'qcm-settings' );
                submit_button( __( 'Save Settings', 'quick-choice-movies' ) );
                ?>
            </form>
        </div>
        <?php
    }
}
