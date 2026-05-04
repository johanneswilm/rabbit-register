<?php
/**
 * Uninstall script for Rabbit Register plugin
 * 
 * This file is executed when the plugin is uninstalled via the WordPress admin.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop custom tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rabbit_registrations");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rabbit_animal_types");

// Remove uploaded files
$upload_dir = wp_upload_dir();
$rabbit_dir = $upload_dir['basedir'] . '/rabbit-registrations';

if (file_exists($rabbit_dir)) {
    // Delete all files in the directory
    $files = glob($rabbit_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    // Remove the directory
    rmdir($rabbit_dir);
}

// Clean up any transients or options if we had them
// delete_option('rabbit_register_some_option');
