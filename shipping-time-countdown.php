<?php
// Prevent direct access to this file
defined('ABSPATH') || exit;

/**
 * Plugin Name: Shipping Time Countdown
 * Plugin URI: https://www.kukumav.net
 * Description: Adds a countdown timer according to the cargo delivery time. Allows the user to set the delivery time and working days.
 * Version: 1.0
 * Author: Kukumav.Net
 * Author URI: https://www.kukumav.net
 * License: GPL2
 * Text Domain: shipping-time-countdown
 * Domain Path: /languages
 */

// Admin dosyasını dahil et
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/settings.php';
}

// Fonksiyonlar dosyasını dahil et
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

// CSS dosyasını yükle
function shipping_enqueue_styles()
{
    wp_enqueue_style('shipping-styles', plugin_dir_url(__FILE__) . 'assets/css/styles.css');

    // Sayfada shortcode varsa JS'i yükle
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


// Activation hook'u dosyanın en üstüne taşıyoruz ve fonksiyonu düzeltiyoruz
register_activation_hook(__FILE__, 'shipping_time_countdown_activate');

function shipping_time_countdown_activate()
{
    // Test için log tutuyoruz
    error_log('Shipping Time Countdown aktivasyon başladı');

    // Kurulum tarihini ayarla
    update_option('shipping_install_date', time());
    error_log('Install date ayarlandı: ' . get_option('shipping_install_date'));

    // Review durumunu başlangıçta pending olarak ayarla
    update_option('shipping_review_status', 'pending');
    error_log('Review status ayarlandı: ' . get_option('shipping_review_status'));

    // Varsayılan ayarları tanımla
    $default_settings = array(
        'shipping_cutoff_time' => '15:00',
        'shipping_working_days' => [1, 2, 3, 4, 5],
        'shipping_same_day_message' => __('SameDayShippingMessagePlaceholder', 'shipping-time-countdown'),
        'shipping_tomorrow_message' => __('TomorrowShippingMessagePlaceholder', 'shipping-time-countdown'),
        'shipping_other_day_message' => __('OtherDaysShippingMessagePlaceholder', 'shipping-time-countdown'),
    );

    // Her bir varsayılan ayarı kontrol et ve yoksa ekle
    foreach ($default_settings as $key => $value) {
        if (false === get_option($key)) {
            update_option($key, $value);
        }
    }

    // Rewrite rules'ı temizle
    flush_rewrite_rules();
}
