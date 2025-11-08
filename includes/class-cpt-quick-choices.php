<?php
/**
 * Custom Post Type: Quick Choices
 *
 * @package QuickChoiceMovies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QCM_CPT_Quick_Choices
 */
class QCM_CPT_Quick_Choices {

    /**
     * Instance of this class
     *
     * @var QCM_CPT_Quick_Choices
     */
    private static $instance = null;

    /**
     * Post type slug
     *
     * @var string
     */
    const POST_TYPE = 'quick_choice';

    /**
     * Get instance
     *
     * @return QCM_CPT_Quick_Choices
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
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );
    }

    /**
     * Register Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Quick Choices', 'Post Type General Name', 'quick-choice-movies' ),
            'singular_name'         => _x( 'Quick Choice', 'Post Type Singular Name', 'quick-choice-movies' ),
            'menu_name'             => __( 'Quick Choices', 'quick-choice-movies' ),
            'name_admin_bar'        => __( 'Quick Choice', 'quick-choice-movies' ),
            'archives'              => __( 'Quick Choice Archives', 'quick-choice-movies' ),
            'attributes'            => __( 'Quick Choice Attributes', 'quick-choice-movies' ),
            'parent_item_colon'     => __( 'Parent Quick Choice:', 'quick-choice-movies' ),
            'all_items'             => __( 'All Quick Choices', 'quick-choice-movies' ),
            'add_new_item'          => __( 'Add New Quick Choice', 'quick-choice-movies' ),
            'add_new'               => __( 'Add New', 'quick-choice-movies' ),
            'new_item'              => __( 'New Quick Choice', 'quick-choice-movies' ),
            'edit_item'             => __( 'Edit Quick Choice', 'quick-choice-movies' ),
            'update_item'           => __( 'Update Quick Choice', 'quick-choice-movies' ),
            'view_item'             => __( 'View Quick Choice', 'quick-choice-movies' ),
            'view_items'            => __( 'View Quick Choices', 'quick-choice-movies' ),
            'search_items'          => __( 'Search Quick Choice', 'quick-choice-movies' ),
            'not_found'             => __( 'Not found', 'quick-choice-movies' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'quick-choice-movies' ),
            'featured_image'        => __( 'Featured Image', 'quick-choice-movies' ),
            'set_featured_image'    => __( 'Set featured image', 'quick-choice-movies' ),
            'remove_featured_image' => __( 'Remove featured image', 'quick-choice-movies' ),
            'use_featured_image'    => __( 'Use as featured image', 'quick-choice-movies' ),
            'insert_into_item'      => __( 'Insert into Quick Choice', 'quick-choice-movies' ),
            'uploaded_to_this_item' => __( 'Uploaded to this Quick Choice', 'quick-choice-movies' ),
            'items_list'            => __( 'Quick Choices list', 'quick-choice-movies' ),
            'items_list_navigation' => __( 'Quick Choices list navigation', 'quick-choice-movies' ),
            'filter_items_list'     => __( 'Filter Quick Choices list', 'quick-choice-movies' ),
        );

        $args = array(
            'label'                 => __( 'Quick Choice', 'quick-choice-movies' ),
            'description'           => __( 'Lists of choices for the quick choice game', 'quick-choice-movies' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions' ),
            'taxonomies'            => array( 'quick_choice_category' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-images-alt2',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'quick-choices',
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Register Custom Taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Categories', 'Taxonomy General Name', 'quick-choice-movies' ),
            'singular_name'              => _x( 'Category', 'Taxonomy Singular Name', 'quick-choice-movies' ),
            'menu_name'                  => __( 'Categories', 'quick-choice-movies' ),
            'all_items'                  => __( 'All Categories', 'quick-choice-movies' ),
            'parent_item'                => __( 'Parent Category', 'quick-choice-movies' ),
            'parent_item_colon'          => __( 'Parent Category:', 'quick-choice-movies' ),
            'new_item_name'              => __( 'New Category Name', 'quick-choice-movies' ),
            'add_new_item'               => __( 'Add New Category', 'quick-choice-movies' ),
            'edit_item'                  => __( 'Edit Category', 'quick-choice-movies' ),
            'update_item'                => __( 'Update Category', 'quick-choice-movies' ),
            'view_item'                  => __( 'View Category', 'quick-choice-movies' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'quick-choice-movies' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'quick-choice-movies' ),
            'choose_from_most_used'      => __( 'Choose from the most used', 'quick-choice-movies' ),
            'popular_items'              => __( 'Popular Categories', 'quick-choice-movies' ),
            'search_items'               => __( 'Search Categories', 'quick-choice-movies' ),
            'not_found'                  => __( 'Not Found', 'quick-choice-movies' ),
            'no_terms'                   => __( 'No categories', 'quick-choice-movies' ),
            'items_list'                 => __( 'Categories list', 'quick-choice-movies' ),
            'items_list_navigation'      => __( 'Categories list navigation', 'quick-choice-movies' ),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
            'rest_base'                  => 'quick-choice-categories',
        );

        register_taxonomy( 'quick_choice_category', array( self::POST_TYPE ), $args );
    }
}
