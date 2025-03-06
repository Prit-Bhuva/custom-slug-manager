<?php

/**
 * Plugin Name: Slug Editor for WordPress
 * Plugin URI: #
 * Description: Easily customize or remove slugs for post types and taxonomies directly from the WordPress admin.
 * Version: 1.0.0
 * Author: #
 * Author URI: #
 * Text Domain: slug-editor-for-wordpress
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Stable tag: 1.0.0
 * 
 * @package Slug_Editore_for_Wordpress
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SEW_VERSION', '1.0.0');
define('SEW_PATH', plugin_dir_path(__FILE__));
define('SEW_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once SEW_PATH . 'includes/class-admin.php';
require_once SEW_PATH . 'includes/class-rewrite.php';

// Initialize the plugin
if (! class_exists('Slug_Editore_for_Wordpress')) {

    class Slug_Editore_for_Wordpress
    {
        private $admin;
        private $rewrite;

        /**
         * Initialize the plugin by setting up the admin menu and settings,
         * and hooking into the WordPress lifecycle to flush rewrite rules.
         */
        public function __construct()
        {
            // Initialize admin menu and settings
            $this->admin = new SEW_Admin();
            $this->rewrite = new SEW_Rewrite();

            // Initialize rewrite rules
            $this->rewrite->init_rewrite_rules();

            // Hook into the WordPress lifecycle
            add_action('admin_enqueue_scripts', array($this, 'sew_scripts_handle'));
        }

        /**
         * Enqueue scripts and styles for the admin settings page.
         */
        public function sew_scripts_handle()
        {
            wp_enqueue_style('sew-slug-manager-css', SEW_URL . 'assets/admin/css/admin-settings-style.css', SEW_VERSION, true);
            wp_enqueue_script('sew-slug-manager-js', SEW_URL . 'assets/admin/js/admin-settings-script.js', SEW_VERSION, true);
        }
    }

    // Instantiate the main plugin class
    $sew_manager = new Slug_Editore_for_Wordpress();
}

/**
 * Register activation and deactivation hooks.
 * 
 * @since 1.0.0
 */
register_activation_hook(__FILE__, 'flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'sew_plugin_deactivate');

/**
 * Function to run on plugin deactivation.
 * Cleans up options and flushes rewrite rules.
 */
function sew_plugin_deactivate() {
    delete_option('sew_manager_settings'); // Remove plugin options
    flush_rewrite_rules(); // Flush rewrite rules
}

/**
 * Flush rewrite rules when the plugin settings are updated.
 * 
 * @since 1.0.0
 */
add_action('update_option_sew_slug_manager_settings', function ($old_value, $new_value) {
    if ($old_value !== $new_value) {
        wp_cache_flush();
        flush_rewrite_rules(true);
    }
}, 10, 2);

/**
 * Flush rewrite rules when the admin settings page is loaded.
 * 
 * @since 1.0.0
 */
add_action('admin_init', function () {
    if (isset($_GET['page']) && $_GET['page'] === 'slug-editor-for-wordpress') {
        // Ensure that rewrite rules are updated
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }
});
