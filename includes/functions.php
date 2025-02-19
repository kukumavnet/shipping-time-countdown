<?php

function custom_delivery_timer()
{
    wp_enqueue_style('shipping-css', plugins_url('/assets/css/styles.css', dirname(__FILE__)));
    wp_enqueue_script('shipping-countdown', plugins_url('/assets/js/countdown.js', dirname(__FILE__)), array('jquery'), '1.0', true);

    wp_localize_script('shipping-countdown', 'shippingCountdown', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('shipping_countdown_nonce')
    ));
    return '<div id="shipping-countdown" class="kargobilgisi"></div>';
}
add_shortcode('custom_delivery_timer', 'custom_delivery_timer');

// AJAX handler for delivery timer
function handle_shipping_countdown_ajax()
{
    check_ajax_referer('shipping_countdown_nonce', 'nonce');

    // Time zone setup
    $timezone = get_option('timezone_string') ?: 'Etc/GMT' . (($offset = get_option('gmt_offset')) > 0 ? '-' : '+') . abs($offset);
    date_default_timezone_set($timezone);

    // Get current time info
    $now = new DateTime();
    $day_of_week = (int)$now->format('N');
    $current_time = strtotime($now->format('H:i'));

    // Get settings
    $settings = [
        'same_day' => get_option('shipping_same_day_message'),
        'tomorrow' => get_option('shipping_tomorrow_message'),
        'other_day' => get_option('shipping_other_day_message'),
        'cutoff' => strtotime(get_option('shipping_cutoff_time', '15:00')),
        'working_days' => get_option('shipping_working_days', [1, 2, 3, 4, 5])
    ];

    $output = calculate_delivery_message($day_of_week, $current_time, $settings);
    wp_send_json_success(['message' => $output]);
}
add_action('wp_ajax_shipping_countdown', 'handle_shipping_countdown_ajax');
add_action('wp_ajax_nopriv_shipping_countdown', 'handle_shipping_countdown_ajax');

function calculate_delivery_message($day_of_week, $current_time, $settings)
{
    $next_delivery_day = find_next_delivery_day($day_of_week, $settings['working_days']);

    // Same day delivery
    if (in_array($day_of_week, $settings['working_days']) && $current_time < $settings['cutoff']) {
        return format_time_message($settings['cutoff'] - $current_time, $settings['same_day']);
    }

    // Next day delivery
    if (in_array($day_of_week, $settings['working_days']) && $current_time >= $settings['cutoff'] && $next_delivery_day === 1) {
        $tomorrow = strtotime('tomorrow ' . date('H:i', $settings['cutoff']));
        return format_time_message($tomorrow - time(), $settings['tomorrow']);
    }

    // Other days delivery
    $delivery_date = date_i18n('j F l', strtotime("+{$next_delivery_day} days"));
    return str_replace('{time}', $delivery_date, $settings['other_day']);
}

function format_time_message($seconds, $message_template)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    $time_str = $hours > 0 ?
        sprintf('%d %s %d %s', $hours, __('Hour', 'shipping-time-countdown'), $minutes, __('Minute', 'shipping-time-countdown')) :
        sprintf('%d %s', $minutes, __('Minute', 'shipping-time-countdown'));

    return str_replace('{time}', $time_str, $message_template);
}

function find_next_delivery_day($current_day, $working_days)
{
    $days_ahead = 0;

    for ($i = 1; $i <= 7; $i++) {
        $next_day = ($current_day + $i - 1) % 7 + 1;
        if (in_array($next_day, $working_days)) {
            $days_ahead = $i;
            break;
        }
    }

    return $days_ahead;
}
