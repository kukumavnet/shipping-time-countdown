<?php
// Prevent direct access to this file
defined('ABSPATH') || exit;

/**
 * Plugin Name: Shipping Time Countdown
 * Plugin URI: https://github.com/kukumavnet/shipping-time-countdown/blob/main/README.md
 * Description: Adds a countdown timer according to the cargo delivery time. Allows setting delivery time and working days.
 * Version: 1.0
 * Author: Kukumav.Net
 * Author URI: https://www.kukumav.net
 * License: GPL v2 or later
 * Text Domain: shipping-time-countdown
 * Domain Path: /languages
 */

// Include admin files if in admin area
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/settings.php';
}

// Include core functions
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

// Load styles and scripts
function shipping_enqueue_styles()
{
    wp_enqueue_style('shipping-styles', plugin_dir_url(__FILE__) . 'assets/css/styles.css');

    // Load JS only if shortcode exists
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'custom_delivery_timer')) {
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'shipping_enqueue_styles');

// Prevent direct access to this file
defined('ABSPATH') or die('No direct script access allowed!');

function shipping_time_countdown_load_textdomain()
{
    load_plugin_textdomain('shipping-time-countdown', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

// Move to init hook instead of plugins_loaded
add_action('init', 'shipping_time_countdown_load_textdomain');



register_activation_hook(__FILE__, 'shipping_time_countdown_activate');

function shipping_time_countdown_activate()
{

    update_option('shipping_install_date', time());

    update_option('shipping_review_status', 'pending');

    $default_settings = array(
        'shipping_cutoff_time' => '15:00',
        'shipping_working_days' => [1, 2, 3, 4, 5],
        'shipping_same_day_message' => __('SameDayShippingMessagePlaceholder', 'shipping-time-countdown'),
        'shipping_tomorrow_message' => __('TomorrowShippingMessagePlaceholder', 'shipping-time-countdown'),
        'shipping_other_day_message' => __('OtherDaysShippingMessagePlaceholder', 'shipping-time-countdown'),
    );
    foreach ($default_settings as $key => $value) {
        if (false === get_option($key)) {
            update_option($key, $value);
        }
    }
    flush_rewrite_rules();
}
