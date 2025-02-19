<?php

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

// Remove all options created by the plugin
delete_option('shipping_install_date');
delete_option('shipping_review_status');
delete_option('shipping_review_reminder_date');
delete_option('shipping_cutoff_time');
delete_option('shipping_working_days');
delete_option('shipping_same_day_message');
delete_option('shipping_other_day_message');
delete_option('shipping_tomorrow_message');

// Clear any cached data
wp_cache_flush();
