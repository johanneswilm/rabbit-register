<?php
/**
 * Plugin Name: Rabbit Register
 * Plugin URI: https://gunnesbo4h.se
 * Description: Registration system for rabbit hotel and small animal boarding at Gunnesbo 4H farm
 * Version: 1.1.0
 * Author: Gunnesbo 4H
 * License: GPL v2 or later
 * Text Domain: rabbit-register
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RABBIT_REGISTER_VERSION', '1.1.0');
define('RABBIT_REGISTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RABBIT_REGISTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load text domain for translations
function rabbit_register_load_textdomain() {
    load_plugin_textdomain('rabbit-register', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'rabbit_register_load_textdomain');

// Include required files
require_once RABBIT_REGISTER_PLUGIN_DIR . 'includes/class-database.php';
require_once RABBIT_REGISTER_PLUGIN_DIR . 'includes/class-animal-types.php';
require_once RABBIT_REGISTER_PLUGIN_DIR . 'includes/class-registration.php';
require_once RABBIT_REGISTER_PLUGIN_DIR . 'includes/class-email.php';
require_once RABBIT_REGISTER_PLUGIN_DIR . 'includes/class-admin.php';
require_once RABBIT_REGISTER_PLUGIN_DIR . 'includes/shortcodes.php';

// Ensure DB tables exist / are up-to-date on every load (handles FTP installs
// and version upgrades where the activation hook never fires).
add_action('plugins_loaded', 'rabbit_register_maybe_update_db');
function rabbit_register_maybe_update_db() {
    if ( get_option( 'rabbit_register_db_version' ) !== RABBIT_REGISTER_VERSION ) {
        Rabbit_Register_Database::create_tables();
        Rabbit_Register_Database::run_migrations();
        update_option( 'rabbit_register_db_version', RABBIT_REGISTER_VERSION );
    }
}

// Activation hook - create database tables
register_activation_hook(__FILE__, 'rabbit_register_activate');
function rabbit_register_activate() {
    Rabbit_Register_Database::create_tables();
    update_option( 'rabbit_register_db_version', RABBIT_REGISTER_VERSION );
    
    // Add default animal types
    $default_animals = array(
        array('name_sv' => 'Kanin', 'name_en' => 'Rabbit', 'slug' => 'rabbit', 'enabled' => 1),
        array('name_sv' => 'Marsvin', 'name_en' => 'Guinea Pig', 'slug' => 'guinea-pig', 'enabled' => 1),
        array('name_sv' => 'Hamster', 'name_en' => 'Hamster', 'slug' => 'hamster', 'enabled' => 1)
    );
    
    foreach ($default_animals as $animal) {
        Rabbit_Register_Animal_Types::add_animal_type($animal);
    }
    
    // Create upload directory
    $upload_dir = wp_upload_dir();
    $rabbit_dir = $upload_dir['basedir'] . '/rabbit-registrations';
    if (!file_exists($rabbit_dir)) {
        wp_mkdir_p($rabbit_dir);
    }
    
    // Add .htaccess to protect uploads
    $htaccess = $rabbit_dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, 'deny from all');
    }
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'rabbit_register_enqueue_scripts');
function rabbit_register_enqueue_scripts() {
    wp_enqueue_style('rabbit-register-style', RABBIT_REGISTER_PLUGIN_URL . 'assets/style.css');
    wp_enqueue_script('rabbit-register-script', RABBIT_REGISTER_PLUGIN_URL . 'assets/script.js', array('jquery'), RABBIT_REGISTER_VERSION, true);
    
    wp_localize_script('rabbit-register-script', 'rabbit_register_ajax', array(
        'ajax_url'             => admin_url('admin-ajax.php'),
        'nonce'                => wp_create_nonce('rabbit_register_nonce'),
        'vaccination_required' => Rabbit_Register_Animal_Types::get_vaccination_requirements(),
    ));
}
