<?php
/**
 * Plugin Name:       Quick Choice Movies
 * Description:       A quick choice movies game
 * Requires at least: 6.6
 * Requires PHP:      7.0
 * Version:           2.1.0
 * Author:            Flavia Bernárdez Rodríguez
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quick-choice-movies
 *
 * @package QuickChoiceMovies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'QCM_VERSION', '0.1.0' );
define( 'QCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QCM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QCM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load plugin modules
 */
function qcm_load_modules() {
    require_once QCM_PLUGIN_DIR . 'includes/class-cpt-quick-choices.php';
    require_once QCM_PLUGIN_DIR . 'includes/class-meta-fields.php';
    require_once QCM_PLUGIN_DIR . 'includes/class-admin-settings.php';
    require_once QCM_PLUGIN_DIR . 'includes/class-api-search.php';
    require_once QCM_PLUGIN_DIR . 'includes/class-game-manager.php';
    require_once QCM_PLUGIN_DIR . 'includes/class-cpt-tier-lists.php';
    require_once QCM_PLUGIN_DIR . 'includes/class-tier-list-meta-fields.php';

    QCM_API_Search::get_instance();
    QCM_CPT_Quick_Choices::get_instance();
    QCM_Meta_Fields::get_instance();
    QCM_Admin_Settings::get_instance();
    QCM_Game_Manager::get_instance();
    QCM_CPT_Tier_Lists::get_instance();
    QCM_Tier_List_Meta_Fields::get_instance();
}
add_action( 'plugins_loaded', 'qcm_load_modules' );

/**
 * Register blocks
 */
function qcm_register_blocks() {
    // Register game block
    register_block_type( QCM_PLUGIN_DIR . 'build/game-block' );

    // Register list block with render callback
    require_once QCM_PLUGIN_DIR . 'includes/list-block-render.php';
    register_block_type( QCM_PLUGIN_DIR . 'build/list-block', array(
        'render_callback' => 'qcm_render_list_block',
    ) );
}
add_action( 'init', 'qcm_register_blocks' );

/**
 * Plugin activation hook
 */
function qcm_activate() {
    require_once QCM_PLUGIN_DIR . 'includes/class-cpt-quick-choices.php';
    QCM_CPT_Quick_Choices::get_instance();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'qcm_activate' );

/**
 * Plugin deactivation hook
 */
function qcm_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'qcm_deactivate' );
