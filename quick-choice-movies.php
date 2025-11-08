<?php
/**
 * Plugin Name:       Quick Choice Movies
 * Description:       A quick choice movies game
 * Requires at least: 6.6
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Flavia Bernárdez Rodríguez
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quick-choice-movies
 *
 * @package QuickChoiceMovies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'QCM_VERSION', '0.1.0' );
define( 'QCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QCM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QCM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load plugin modules
 */
function qcm_load_modules() {
    // Load CPT
    require_once QCM_PLUGIN_DIR . 'includes/class-cpt-quick-choices.php';

    // Load Meta Fields
    require_once QCM_PLUGIN_DIR . 'includes/class-meta-fields.php';

    // Load Admin Settings
    require_once QCM_PLUGIN_DIR . 'includes/class-admin-settings.php';

    // Load Game Manager
    require_once QCM_PLUGIN_DIR . 'includes/class-game-manager.php';

    // Initialize modules
    QCM_CPT_Quick_Choices::get_instance();
    QCM_Meta_Fields::get_instance();
    QCM_Admin_Settings::get_instance();
    QCM_Game_Manager::get_instance();
}
add_action( 'plugins_loaded', 'qcm_load_modules' );

/**
 * Register block
 */
function qcm_register_block() {
    register_block_type( QCM_PLUGIN_DIR . 'build/game-block' );
}
add_action( 'init', 'qcm_register_block' );

/**
 * Plugin activation hook
 */
function qcm_activate() {
    // Load CPT class to register post type
    require_once QCM_PLUGIN_DIR . 'includes/class-cpt-quick-choices.php';
    QCM_CPT_Quick_Choices::get_instance();

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'qcm_activate' );

/**
 * Plugin deactivation hook
 */
function qcm_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'qcm_deactivate' );
