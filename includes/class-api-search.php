<?php
/**
 * Shared API Search
 *
 * @package QuickChoiceMovies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QCM_API_Search
 * Centralized API search for TMDB, RAWG, and Open Library.
 */
class QCM_API_Search {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_qcm_api_search', array( $this, 'ajax_search' ) );
    }

    /**
     * AJAX handler for API search
     */
    public function ajax_search() {
        check_ajax_referer( 'qcm_api_search', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $api_source = isset( $_POST['api_source'] ) ? sanitize_text_field( $_POST['api_source'] ) : '';
        $query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';
        $language = isset( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : '';

        if ( ! $api_source || ! $query ) {
            wp_send_json_error( array( 'message' => 'Missing parameters' ) );
        }

        $results = $this->search( $api_source, $query, $language );

        if ( false === $results ) {
            wp_send_json_error( array( 'message' => 'Invalid API source' ) );
        }

        wp_send_json_success( array( 'results' => $results ) );
    }

    /**
     * Search an API source
     *
     * @param string $api_source API source (tmdb, rawg, openlibrary)
     * @param string $query Search query
     * @return array|false Results array or false if invalid source
     */
    public function search( $api_source, $query, $language = '' ) {
        switch ( $api_source ) {
            case 'tmdb':
                return $this->search_tmdb( $query, $language );
            case 'rawg':
                return $this->search_rawg( $query, $language );
            case 'openlibrary':
                return $this->search_openlibrary( $query, $language );
            default:
                return false;
        }
    }

    /**
     * Search TMDB API
     */
    private function search_tmdb( $query, $language = '' ) {
        $api_key = get_option( 'qcm_tmdb_api_key', '' );

        if ( ! $api_key ) {
            return array();
        }

        $params = array( 'api_key' => $api_key );
        if ( $language ) {
            $params['language'] = $language;
        }
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
            $credits_params = array( 'api_key' => $api_key );
            if ( $language ) {
                $credits_params['language'] = $language;
            }
            $credits_url = add_query_arg(
                $credits_params,
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
    private function search_rawg( $query, $language = '' ) {
        $api_key = get_option( 'qcm_rawg_api_key', '' );

        if ( ! $api_key ) {
            return array();
        }

        $params = array(
            'key'    => $api_key,
            'search' => $query,
        );
        if ( $language ) {
            $params['language'] = substr( $language, 0, 2 );
        }
        $url = add_query_arg( $params, 'https://api.rawg.io/api/games' );

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
    private function search_openlibrary( $query, $language = '' ) {
        $params = array(
            'q'     => $query,
            'limit' => 20,
        );
        if ( $language ) {
            $params['language'] = substr( $language, 0, 3 );
        }
        $url = add_query_arg( $params, 'https://openlibrary.org/search.json' );

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
}
