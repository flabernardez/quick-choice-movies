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
        add_action( 'wp_ajax_qcm_search_api', array( $this, 'ajax_search_api' ) );
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
        // Check if the qcm-game block is present in the content
        if ( is_singular() ) {
            global $post;
            if ( has_block( 'qcm/game-block', $post ) ) {
                return true;
            }
        }
        return false;
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

    /**
     * AJAX handler to search external APIs
     */
    public function ajax_search_api() {
        check_ajax_referer( 'qcm_meta_fields', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'quick-choice-movies' ) ) );
        }

        $api_source = isset( $_POST['api_source'] ) ? sanitize_text_field( $_POST['api_source'] ) : '';
        $query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';

        if ( ! $api_source || ! $query ) {
            wp_send_json_error( array( 'message' => __( 'Missing parameters', 'quick-choice-movies' ) ) );
        }

        $results = array();

        switch ( $api_source ) {
            case 'tmdb':
                $results = $this->search_tmdb( $query );
                break;
            case 'rawg':
                $results = $this->search_rawg( $query );
                break;
            case 'google_books':
                $results = $this->search_google_books( $query );
                break;
            default:
                wp_send_json_error( array( 'message' => __( 'Invalid API source', 'quick-choice-movies' ) ) );
        }

        wp_send_json_success( array( 'results' => $results ) );
    }

    /**
     * Search TMDB API
     *
     * @param string $query Search query
     * @return array
     */
    private function search_tmdb( $query ) {
        $api_key = get_option( 'qcm_tmdb_api_key', '' );

        if ( ! $api_key ) {
            return array();
        }

        $url = add_query_arg(
            array(
                'api_key' => $api_key,
                'query'   => $query,
            ),
            'https://api.themoviedb.org/3/search/movie'
        );

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! isset( $body['results'] ) ) {
            return array();
        }

        $results = array();
        foreach ( $body['results'] as $movie ) {
            $results[] = array(
                'id'    => $movie['id'],
                'title' => $movie['title'],
                'image' => 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'],
                'year'  => isset( $movie['release_date'] ) ? substr( $movie['release_date'], 0, 4 ) : '',
            );
        }

        return $results;
    }

    /**
     * Search RAWG API (Video Games)
     *
     * @param string $query Search query
     * @return array
     */
    private function search_rawg( $query ) {
        $api_key = get_option( 'qcm_rawg_api_key', '' );

        if ( ! $api_key ) {
            return array();
        }

        $url = add_query_arg(
            array(
                'key'    => $api_key,
                'search' => $query,
            ),
            'https://api.rawg.io/api/games'
        );

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! isset( $body['results'] ) ) {
            return array();
        }

        $results = array();
        foreach ( $body['results'] as $game ) {
            $results[] = array(
                'id'    => $game['id'],
                'title' => $game['name'],
                'image' => $game['background_image'],
                'year'  => isset( $game['released'] ) ? substr( $game['released'], 0, 4 ) : '',
            );
        }

        return $results;
    }

    /**
     * Search Google Books API
     *
     * @param string $query Search query
     * @return array
     */
    private function search_google_books( $query ) {
        $api_key = get_option( 'qcm_google_books_api_key', '' );

        if ( ! $api_key ) {
            return array();
        }

        $url = add_query_arg(
            array(
                'key' => $api_key,
                'q'   => $query,
            ),
            'https://www.googleapis.com/books/v1/volumes'
        );

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! isset( $body['items'] ) ) {
            return array();
        }

        $results = array();
        foreach ( $body['items'] as $book ) {
            $volume_info = $book['volumeInfo'];
            $results[] = array(
                'id'    => $book['id'],
                'title' => $volume_info['title'],
                'image' => isset( $volume_info['imageLinks']['thumbnail'] ) ? $volume_info['imageLinks']['thumbnail'] : '',
                'year'  => isset( $volume_info['publishedDate'] ) ? substr( $volume_info['publishedDate'], 0, 4 ) : '',
            );
        }

        return $results;
    }
}
