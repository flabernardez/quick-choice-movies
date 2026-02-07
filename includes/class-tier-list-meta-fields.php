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
        add_action( 'wp_ajax_qcm_search_api_tierlist', array( $this, 'ajax_search_api' ) );
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

    /**
     * AJAX handler to search external APIs (reuses existing logic)
     */
    public function ajax_search_api() {
        check_ajax_referer( 'qcm_save_tier_list', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $api_source = isset( $_POST['api_source'] ) ? sanitize_text_field( $_POST['api_source'] ) : '';
        $query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';

        if ( ! $api_source || ! $query ) {
            wp_send_json_error( array( 'message' => 'Missing parameters' ) );
        }

        // Reuse the search methods from QCM_Meta_Fields
        $meta_fields = QCM_Meta_Fields::get_instance();
        $results = array();

        switch ( $api_source ) {
            case 'tmdb':
                $results = $this->search_tmdb( $query );
                break;
            case 'rawg':
                $results = $this->search_rawg( $query );
                break;
            case 'openlibrary':
                $results = $this->search_openlibrary( $query );
                break;
            default:
                wp_send_json_error( array( 'message' => 'Invalid API source' ) );
        }

        wp_send_json_success( array( 'results' => $results ) );
    }

    /**
     * Search TMDB API
     */
    private function search_tmdb( $query ) {
        $api_key = get_option( 'qcm_tmdb_api_key', '' );

        if ( ! $api_key ) {
            return array();
        }

        $params = array( 'api_key' => $api_key );
        $endpoint = 'https://api.themoviedb.org/3/search/movie';

        if ( preg_match('/\b(actor|director|cast|person)\b/i', $query) ) {
            $endpoint = 'https://api.themoviedb.org/3/search/person';
            $query = preg_replace('/\b(actor|director|cast|person)\b/i', '', $query);
            $query = trim( $query );
        }

        $params['query'] = $query;
        $url = add_query_arg( $params, $endpoint );
        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! isset( $body['results'] ) ) {
            return array();
        }

        $results = array();

        if ( strpos( $endpoint, 'person' ) !== false && ! empty( $body['results'] ) ) {
            $person_id = $body['results'][0]['id'];
            $credits_url = add_query_arg(
                array( 'api_key' => $api_key ),
                "https://api.themoviedb.org/3/person/{$person_id}/movie_credits"
            );

            $credits_response = wp_remote_get( $credits_url );
            if ( ! is_wp_error( $credits_response ) ) {
                $credits_body = json_decode( wp_remote_retrieve_body( $credits_response ), true );
                $movies = array_merge(
                    isset( $credits_body['cast'] ) ? $credits_body['cast'] : array(),
                    isset( $credits_body['crew'] ) ? $credits_body['crew'] : array()
                );

                $unique_movies = array();
                foreach ( $movies as $movie ) {
                    if ( ! isset( $unique_movies[ $movie['id'] ] ) ) {
                        $unique_movies[ $movie['id'] ] = $movie;
                    }
                }

                foreach ( $unique_movies as $movie ) {
                    if ( empty( $movie['poster_path'] ) ) {
                        continue;
                    }

                    $results[] = array(
                        'id'    => $movie['id'],
                        'title' => isset( $movie['title'] ) ? $movie['title'] : $movie['name'],
                        'image' => 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'],
                        'year'  => isset( $movie['release_date'] ) ? substr( $movie['release_date'], 0, 4 ) : '',
                    );

                    if ( count( $results ) >= 20 ) {
                        break;
                    }
                }
            }
        } else {
            foreach ( $body['results'] as $movie ) {
                if ( empty( $movie['poster_path'] ) ) {
                    continue;
                }

                $results[] = array(
                    'id'    => $movie['id'],
                    'title' => $movie['title'],
                    'image' => 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'],
                    'year'  => isset( $movie['release_date'] ) ? substr( $movie['release_date'], 0, 4 ) : '',
                );
            }
        }

        return $results;
    }

    /**
     * Search RAWG API
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
            if ( empty( $game['background_image'] ) ) {
                continue;
            }

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
     * Search Open Library API
     */
    private function search_openlibrary( $query ) {
        $url = add_query_arg(
            array(
                'q'     => $query,
                'limit' => 20,
            ),
            'https://openlibrary.org/search.json'
        );

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! isset( $body['docs'] ) ) {
            return array();
        }

        $results = array();
        foreach ( $body['docs'] as $book ) {
            if ( empty( $book['cover_i'] ) ) {
                continue;
            }

            $results[] = array(
                'id'    => isset( $book['key'] ) ? $book['key'] : uniqid(),
                'title' => isset( $book['title'] ) ? $book['title'] : '',
                'image' => 'https://covers.openlibrary.org/b/id/' . $book['cover_i'] . '-M.jpg',
                'year'  => isset( $book['first_publish_year'] ) ? $book['first_publish_year'] : '',
            );
        }

        return $results;
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

        $asset_file = include( QCM_PLUGIN_DIR . 'build/tier-list-meta-fields/index.asset.php' );

        wp_enqueue_script(
            'qcm-tier-list-meta-fields',
            QCM_PLUGIN_URL . 'build/tier-list-meta-fields/index.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        wp_enqueue_style(
            'qcm-tier-list-meta-fields',
            QCM_PLUGIN_URL . 'build/tier-list-meta-fields/index.css',
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
                'postId'       => $post_id,
                'currentItems' => $current_items,
                'currentTiers' => $current_tiers,
                'defaultTiers' => self::DEFAULT_TIERS,
            )
        );
    }
}
