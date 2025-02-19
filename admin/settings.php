<?php
// Prevent direct access to this file
defined('ABSPATH') || exit;

// Update review card function
function render_review_card()
{
    if (!should_show_review()) {
        return;
    }
?>
    <div class="settings-card review-card mt-3">
        <h3><?php echo esc_html__('DoYouLikeOurPlugin', 'shipping-time-countdown'); ?></h3>
        <p><?php echo esc_html__('IfYouFindOurPluginUseful', 'shipping-time-countdown'); ?></p>
        <div class="review-actions">
            <a href="https://wordpress.org/support/plugin/shipping-time-countdown/reviews/#new-post"
                target="_blank"
                class="button button-primary review-now">
                <?php echo esc_html__('ReviewNow', 'shipping-time-countdown'); ?>
            </a>
            <button class="button remind-later">
                <?php echo esc_html__('RemindMeLater', 'shipping-time-countdown'); ?>
            </button>
            <button class="button already-reviewed">
                <?php echo esc_html__('IAlreadyReviewed', 'shipping-time-countdown'); ?>
            </button>
        </div>
    </div>
<?php
}

function should_show_review()
{
    $install_date = get_option('shipping_install_date');
    $review_status = get_option('shipping_review_status', 'pending');
    $reminder_date = get_option('shipping_review_reminder_date', 0);
    $current_time = time();

    // Don't show if already reviewed
    if ($review_status === 'reviewed') {
        return false;
    }

    // Show if pending and installed more than 7 days ago
    if ($review_status === 'pending' && $install_date) {
        return true;
    }

    // Show if postponed and reminder date has passed
    if ($review_status === 'postponed' && $current_time > $reminder_date) {
        return true;
    }

    return false;
}

// Add main menu and submenu pages
function kukumav_menu()
{
    // Add main menu
    add_menu_page(
        'Kukumav.NET', // Menu title
        'Kukumav', // Menu name
        'manage_options', // Capability
        'kukumav-net', // Menu slug
        'kukumav_net_settings_page', // Function to display the page
        'dashicons-awards', // Dashicons or custom icon
        2 // Position
    );

    // Add shipping settings submenu
    add_submenu_page(
        'kukumav-net', // Parent menu slug
        __('PluginSettings', 'shipping-time-countdown'), // Menu name
        __('PluginName', 'shipping-time-countdown'), // Menu name
        'manage_options', // Capability
        'shipping-settings', // Submenu slug
        'shipping_settings_page' // Function to display the page
    );
}
add_action('admin_menu', 'kukumav_menu');

function kukumav_net_settings_page()
{

    if (!current_user_can('manage_options')) {
        return;
    }

    // Review card display control
    $install_date = get_option('shipping_install_date');
    $review_status = get_option('shipping_review_status', 'pending');
    $reminder_date = get_option('shipping_review_reminder_date', 0);
    $current_time = time();
    $show_review = false;

    // Review card display conditions
    if ($review_status === 'pending' && $install_date && ($current_time - $install_date > 7 * DAY_IN_SECONDS)) {
        $show_review = true;
    } elseif ($review_status === 'postponed' && $current_time > $reminder_date) {
        $show_review = true;
    }

    // Don't show if already reviewed
    if ($review_status === 'reviewed') {
        $show_review = false;
    }

    // Enqueue WordPress core styles and scripts
    wp_enqueue_style('shipping-admin-css', plugins_url('/assets/css/admin.css', dirname(__FILE__)));
    wp_enqueue_style('shipping-slider-css', plugins_url('/assets/css/slider.css', dirname(__FILE__)));

    // Process RSS feed
    $rss = fetch_feed('https://www.kukumav.net/blog/feed/');
    $maxitems = 0;
    $rss_items = array();

    if (!is_wp_error($rss)) {
        $maxitems = $rss->get_item_quantity(9);
        $rss_items = $rss->get_items(0, $maxitems);
    }

    // Set grid layout for mobile/desktop
    $items_per_slide = wp_is_mobile() ? 1 : 3;

    // Display page content
?>
    <div class="wrap kukumav-admin">
        <div class="settings-card">
            <div class="plugin-header">
                <img src="<?php echo plugins_url('/assets/images/shipping-time-countdown-logo.png', dirname(__FILE__)); ?>"
                    alt="Shipping Time Countdown Logo"
                    class="plugin-logo">
                <div class="plugin-info">
                    <h3><?php echo esc_html(__('PluginName', 'shipping-time-countdown')); ?></h3>
                    <p><?php echo esc_html(__('PluginDescription', 'shipping-time-countdown')); ?></p>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <h1 class="kukumav-title ">Kukumav.Net Blog</h1>
        </div>
        <div class="blog-slideshow">
            <?php if ($maxitems > 0) : ?>
                <button class="slide-nav slide-prev" aria-label="Previous slide">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <button class="slide-nav slide-next" aria-label="Next slide">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
                <div class="slides-container">
                    <?php
                    $counter = 0;
                    $page_counter = 0;

                    echo '<div id="slide-' . $page_counter . '" class="slide' . ($page_counter === 0 ? ' active' : '') . '">';
                    echo '<div class="blog-grid">';

                    foreach ($rss_items as $item) {
                        // Show 1 (mobile) or 3 (desktop) posts per page
                        if ($counter > 0 && $counter % $items_per_slide === 0) {
                            echo '</div></div>';
                            $page_counter++;
                            echo '<div id="slide-' . $page_counter . '" class="slide">';
                            echo '<div class="blog-grid">';
                        }

                        // Blog item content
                        $thumbnail = '';
                        $content = $item->get_content();
                        $excerpt = wp_trim_words(strip_tags($content), 20, '...');

                        if (preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $content, $matches)) {
                            $thumbnail = $matches['src'];
                        }
                    ?>
                        <div class="blog-item">
                            <?php if ($thumbnail) : ?>
                                <div class="blog-image">
                                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($item->get_title()); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="blog-content">
                                <h3>
                                    <a href="<?php echo esc_url($item->get_permalink()); ?>" target="_blank">
                                        <?php echo esc_html($item->get_title()); ?>
                                    </a>
                                </h3>
                                <div class="blog-excerpt">
                                    <?php echo esc_html($excerpt); ?>
                                </div>
                                <div class="meta">
                                    <span class="author"><?php echo esc_html($item->get_author()->get_name()); ?></span>
                                    <span class="date">
                                        <?php
                                        $date = $item->get_date('U');
                                        echo date_i18n(
                                            get_option('date_format'),
                                            $date + (get_option('gmt_offset') * HOUR_IN_SECONDS)
                                        );
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php
                        $counter++;
                    }
                    echo '</div></div>';
                    ?>
                </div>
                <div class="slide-dots">
                    <?php
                    // Adjust dot count for mobile
                    $total_pages = ceil($maxitems / (wp_is_mobile() ? 1 : 3));
                    for ($i = 0; $i < $total_pages; $i++) {
                        echo '<button class="dot' . ($i === 0 ? ' active' : '') . '" data-slide="' . $i . '"></button>';
                    }
                    ?>
                </div>
            <?php else : ?>
                <p><?php _e('No blog posts found.', 'shipping-time-countdown'); ?></p>
            <?php endif; ?>
        </div>
        <div class="more-posts-link">
            <a href="https://www.kukumav.net/blog" target="_blank" class="button button-primary">
                <span class="dashicons dashicons-welcome-write-blog"></span>
                <?php echo esc_html__('MoreBlogPosts', 'shipping-time-countdown'); ?>
            </a>
        </div>
        <?php render_review_card(); ?>

    </div>
<?php
}

function shipping_settings_page()
{

    if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
        return;
    }

    // Check if we should show review 
    $install_date = get_option('shipping_install_date');
    $review_status = get_option('shipping_review_status', 'pending');
    $reminder_date = get_option('shipping_review_reminder_date', 0);
    $current_time = time();
    $show_review = false;

    // Only show review if:
    // 1. Status is pending AND installed for more than 7 days
    // 2. Status is postponed AND reminder date has passed
    if ($review_status === 'pending' && $install_date && ($current_time - $install_date > 7 * DAY_IN_SECONDS)) {
        $show_review = true;
    } elseif ($review_status === 'postponed' && $current_time > $reminder_date) {
        $show_review = true;
    }

    // Don't show if already reviewed
    if ($review_status === 'reviewed') {
        $show_review = false;
    }

    if ($show_review) {
        render_review_card();
    }
?>

    <div class="wrap kukumav-admin">
        <h1 class="kukumav-title"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="settings-container ">
            <form method="post" action="options.php" class="modern-form settings-card">
                <?php
                settings_fields('shipping_settings_group');
                do_settings_sections('shipping-settings');
                submit_button(__('SaveSettings', 'shipping-time-countdown'), 'primary large');
                ?>
            </form>
            <div class="settings-sidebar">

                <div class="settings-card">
                    <h2 class="kukumav-title"><?php echo esc_html(__('ShortcodeUsage', 'shipping-time-countdown')); ?></h2>
                    <p><?php echo esc_html(__('ShortcodeUsageDescription', 'shipping-time-countdown')); ?></p>
                    <code class="shortcode-display">[custom_delivery_timer]</code>
                </div>

                <?php render_review_card(); ?>
            </div>
        </div>
    </div>
<?php
}

// Initialize plugin settings
function shipping_register_settings()
{
    // Register setting groups
    register_setting(
        'shipping_settings_group',
        'shipping_same_day_message',
        array('sanitize_callback' => 'wp_kses_post')
    );
    register_setting(
        'shipping_settings_group',
        'shipping_other_day_message',
        array('sanitize_callback' => 'wp_kses_post')
    );

    register_setting(
        'shipping_settings_group',
        'shipping_tomorrow_message',
        array('sanitize_callback' => 'wp_kses_post')
    );


    register_setting(
        'shipping_settings_group',
        'shipping_cutoff_time',
        array('sanitize_callback' => 'sanitize_text_field')
    );
    register_setting(
        'shipping_settings_group',
        'shipping_working_days',
        array('sanitize_callback' => 'shipping_sanitize_working_days')
    );

    // Add settings fields
    add_settings_section('shipping_settings_section', __('GeneralSettings', 'shipping-time-countdown'), null, 'shipping-settings');

    add_settings_field(
        'shipping_same_day_message',
        __('SameDayShippingMessage', 'shipping-time-countdown'),
        'shipping_same_day_message_field_callback',
        'shipping-settings',
        'shipping_settings_section'
    );

    add_settings_field(
        'shipping_tomorrow_message',
        __('TomorrowShippingMessage', 'shipping-time-countdown'),
        'shipping_tomorrow_message_field_callback',
        'shipping-settings',
        'shipping_settings_section'
    );

    add_settings_field(
        'shipping_other_day_message',
        __('OtherDaysShippingMessage', 'shipping-time-countdown'),
        'shipping_other_day_message_field_callback',
        'shipping-settings',
        'shipping_settings_section'
    );

    add_settings_field(
        'shipping_cutoff_time',
        __('ShippingDeliveryTime', 'shipping-time-countdown'),
        'shipping_cutoff_time_field_callback',
        'shipping-settings',
        'shipping_settings_section'
    );

    add_settings_field(
        'shipping_working_days',
        __('ShippingDays', 'shipping-time-countdown'),
        'shipping_working_days_field_callback',
        'shipping-settings',
        'shipping_settings_section'
    );
}
add_action('admin_init', 'shipping_register_settings');

// Handle AJAX review updates
function handle_shipping_review_ajax()
{
    check_ajax_referer('shipping_review_nonce', 'nonce');

    $status = sanitize_text_field($_POST['status']);
    $valid_statuses = ['postponed', 'reviewed'];

    // Validate status
    if (!in_array($status, $valid_statuses)) {
        wp_send_json_error('Invalid status');
        return;
    }

    // Update review status
    if ($status === 'postponed') {
        // Set reminder for 7 days later
        update_option('shipping_review_status', 'postponed');
        update_option('shipping_review_reminder_date', time() + (7 * DAY_IN_SECONDS));
    } else {
        // Mark as permanently reviewed
        update_option('shipping_review_status', 'reviewed');
        delete_option('shipping_review_reminder_date'); // Clear reminder date
    }

    wp_send_json_success(['status' => $status]);
}
add_action('wp_ajax_update_shipping_review_status', 'handle_shipping_review_ajax');

function shipping_add_underline_button($buttons)
{
    // Add underline button to the toolbar
    array_push($buttons, 'underline');
    return $buttons;
}
add_filter('mce_buttons', 'shipping_add_underline_button');
function shipping_same_day_message_field_callback()
{
    $message = get_option('shipping_same_day_message', __('SameDayShippingMessagePlaceholder', 'shipping-time-countdown'));
    $settings = array(
        'textarea_name' => 'shipping_same_day_message',
        'textarea_rows' => 3,
        'editor_class'  => 'large-text',
    );
    wp_editor($message, 'shipping_same_day_message', $settings);
    echo '<span>' . __('ParametersDescription', 'shipping-time-countdown') . ': <b>{time}</b></span>';
}
function shipping_tomorrow_message_field_callback()
{
    $message = get_option('shipping_tomorrow_message', __('TomorrowShippingMessagePlaceholder', 'shipping-time-countdown'));
    $settings = array(
        'textarea_name' => 'shipping_tomorrow_message',
        'textarea_rows' => 3,
        'editor_class'  => 'large-text',
    );
    wp_editor($message, 'shipping_tomorrow_message', $settings);
    echo '<span>' . __('ParametersDescription', 'shipping-time-countdown') . ': <b>{time}</b></span>';
}
function shipping_other_day_message_field_callback()
{
    $message = get_option('shipping_other_day_message', __('OtherDaysShippingMessagePlaceholder', 'shipping-time-countdown'));
    $settings = array(
        'textarea_name' => 'shipping_other_day_message',
        'textarea_rows' => 3,
        'editor_class'  => 'large-text',
    );
    wp_editor($message, 'shipping_other_day_message', $settings);
    echo '<span>' . __('ParametersDescription', 'shipping-time-countdown') . ': <b>{time}</b></span>';
}

function shipping_cutoff_time_field_callback()
{
    $cutoff_time = get_option('shipping_cutoff_time', '15:00');
    echo '<input type="time" id="shipping_cutoff_time" name="shipping_cutoff_time" value="' . esc_attr($cutoff_time) . '">';
}

function shipping_working_days_field_callback()
{
    $working_days = get_option('shipping_working_days', [1, 2, 3, 4, 5]);
    if (empty($working_days)) {
        $working_days = [];
    }
    $days = [
        1 => __('Monday', 'shipping-time-countdown'),
        2 => __('Tuesday', 'shipping-time-countdown'),
        3 => __('Wednesday', 'shipping-time-countdown'),
        4 => __('Thursday', 'shipping-time-countdown'),
        5 => __('Friday', 'shipping-time-countdown'),
        6 => __('Saturday', 'shipping-time-countdown'),
        7 => __('Sunday', 'shipping-time-countdown')
    ];
    echo '<div class="working-days-group">';
    foreach ($days as $key => $day) {
        $checked = in_array($key, $working_days) ? 'checked' : '';
        echo "<label><input type='checkbox' name='shipping_working_days[]' value='{$key}' {$checked}> {$day}</label>";
    }
    echo '</div>';
}

function shipping_sanitize_working_days($input)
{
    if (!is_array($input)) {
        return array();
    }
    return array_map('absint', $input);
}


// Set initial settings on plugin activation
function shipping_plugin_activation()
{
    if (!get_option('shipping_install_date')) {
        update_option('shipping_install_date', time());
        update_option('shipping_review_status', 'pending');
        delete_option('shipping_review_reminder_date');
    }
}
register_activation_hook(__FILE__, 'shipping_plugin_activation');

// Update JavaScript code
add_action('admin_footer', 'shipping_review_scripts');
function shipping_review_scripts()
{
?>
    <script>
        jQuery(document).ready(function($) {
            function handleReviewAction(status) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_shipping_review_status',
                        status: status,
                        nonce: '<?php echo wp_create_nonce('shipping_review_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.review-card').slideUp();
                        }
                    }
                });
            }

            $('.review-now').on('click', function() {
                handleReviewAction('reviewed');
            });

            $('.remind-later').on('click', function() {
                handleReviewAction('postponed');
            });

            $('.already-reviewed').on('click', function() {
                handleReviewAction('reviewed');
            });
        });
    </script>
<?php
}

// Load required styles and scripts
function kukumav_admin_enqueue_scripts()
{
    wp_enqueue_style('shipping-admin-css', plugins_url('/assets/css/admin.css', dirname(__FILE__)));
    wp_enqueue_style('shipping-slider-css', plugins_url('/assets/css/slider.css', dirname(__FILE__)));
    wp_enqueue_script('shipping-admin-js', plugins_url('/assets/js/admin.js', dirname(__FILE__)), array('jquery'), '', true);
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_style('wp-jquery-ui-dialog');

    // Pass nonce to JavaScript
    wp_localize_script('shipping-admin-js', 'kukumavAdmin', array(
        'nonce' => wp_create_nonce('shipping_review_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'kukumav_admin_enqueue_scripts');

// Include settings page view
function include_settings_page_view()
{
    if (!current_user_can('manage_options')) return;

    // Prepare blog feed
    $maxitems = 0;
    $rss_items = array();
    $rss = fetch_feed('https://www.kukumav.net/blog/feed/');

    if (!is_wp_error($rss)) {
        $maxitems = $rss->get_item_quantity(9);
        $rss_items = $rss->get_items(0, $maxitems);
    }

    // Display page content
    include_once plugin_dir_path(__FILE__) . 'views/settings-page.php';
}
