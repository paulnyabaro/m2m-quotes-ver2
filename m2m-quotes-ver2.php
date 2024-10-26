<?php
/**
 * Plugin Name: M2M Quotes Ver2
 * Description: Display quotes that change every 24 hours, with analytics for likes and dislikes, customizable links, and sharing options.
 * Version: 2.1
 * Author: Mind To Matter
 */

// Register Custom Post Type for Quotes
function m2m_quotes_ver2_custom_post_type() {
    register_post_type('m2m_quotes', array(
        'labels' => array(
            'name' => __('Quotes'),
            'singular_name' => __('Quote')
        ),
        'public' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-format-quote',
        'supports' => array('title', 'editor', 'custom-fields'),
    ));
}
add_action('init', 'm2m_quotes_ver2_custom_post_type');

// Add Meta Boxes for Quote Details
function m2m_quotes_ver2_add_meta_boxes() {
    add_meta_box('m2m_quotes_meta', 'Quote Details', 'm2m_quotes_ver2_meta_callback', 'm2m_quotes', 'normal', 'high');
}
add_action('add_meta_boxes', 'm2m_quotes_ver2_add_meta_boxes');

function m2m_quotes_ver2_meta_callback($post) {
    $author = get_post_meta($post->ID, '_m2m_quote_author', true);
    $role = get_post_meta($post->ID, '_m2m_quote_role', true);
    echo '<p><label for="m2m_quote_author">Author:</label> <input type="text" id="m2m_quote_author" name="m2m_quote_author" value="' . esc_attr($author) . '" /></p>';
    echo '<p><label for="m2m_quote_role">Role:</label> <input type="text" id="m2m_quote_role" name="m2m_quote_role" value="' . esc_attr($role) . '" /></p>';
}

// Save Meta Data
function m2m_quotes_ver2_save_meta_box_data($post_id) {
    if (isset($_POST['m2m_quote_author'])) {
        update_post_meta($post_id, '_m2m_quote_author', sanitize_text_field($_POST['m2m_quote_author']));
    }
    if (isset($_POST['m2m_quote_role'])) {
        update_post_meta($post_id, '_m2m_quote_role', sanitize_text_field($_POST['m2m_quote_role']));
    }
}
add_action('save_post', 'm2m_quotes_ver2_save_meta_box_data');

// Display Quotes with Shortcode
function m2m_quotes_ver2_shortcode() {
    $args = array(
        'post_type' => 'm2m_quotes',
        'posts_per_page' => 1,
        'orderby' => 'rand',
    );
    $quote_query = new WP_Query($args);

    if ($quote_query->have_posts()) {
        while ($quote_query->have_posts()) {
            $quote_query->the_post();
            $quote = get_the_content();
            $author = get_post_meta(get_the_ID(), '_m2m_quote_author', true);
            $role = get_post_meta(get_the_ID(), '_m2m_quote_role', true);
            $likes = get_post_meta(get_the_ID(), '_m2m_quote_likes', true) ?: 0;
            $dislikes = get_post_meta(get_the_ID(), '_m2m_quote_dislikes', true) ?: 0;

            // Display Quote
            echo '<div class="m2m-quote">';
            echo '<p>' . esc_html($quote) . '</p>';
            echo '<p><strong>- ' . esc_html($author) . ', ' . esc_html($role) . '</strong></p>';
            
            // Display Like/Dislike buttons
            echo '<div class="m2m-votes">';
            echo '<button class="m2m-like-btn" data-quote-id="' . get_the_ID() . '">👍 (' . esc_html($likes) . ')</button>';
            echo '<button class="m2m-dislike-btn" data-quote-id="' . get_the_ID() . '">👎 (' . esc_html($dislikes) . ')</button>';
            echo '</div>';

            // Share buttons
            echo '<div class="m2m-share-buttons">';
            echo '<a href="https://twitter.com/share?text=' . urlencode($quote) . '" target="_blank">Twitter</a>';
            echo '<a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode(get_permalink()) . '" target="_blank">Facebook</a>';
            echo '<a href="https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode(get_permalink()) . '" target="_blank">LinkedIn</a>';
            echo '<button class="m2m-copy-link" data-link="' . get_permalink() . '">Copy Link</button>';
            echo '</div>';

            echo '</div>';
        }
        wp_reset_postdata();
    } else {
        echo '<p>No quote available at the moment.</p>';
    }
}
add_shortcode('m2m_quotes_ver2', 'm2m_quotes_ver2_shortcode');

// Enqueue Frontend Styles and Scripts
function m2m_quotes_ver2_enqueue_assets() {
    wp_enqueue_style('m2m-quotes-ver2-css', plugins_url('/css/m2m-quotes-ver2.css', __FILE__));
    wp_enqueue_script('m2m-quotes-ver2-js', plugins_url('/js/m2m-quotes-ver2.js', __FILE__), array('jquery'), null, true);

    // Localize script to send ajax_url
    wp_localize_script('m2m-quotes-ver2-js', 'm2m_quotes_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'm2m_quotes_ver2_enqueue_assets');

// AJAX Handlers for Likes/Dislikes
function m2m_quotes_ver2_like_dislike() {
    if (isset($_POST['quote_id']) && isset($_POST['vote_type'])) {
        $quote_id = intval($_POST['quote_id']);
        $vote_type = sanitize_text_field($_POST['vote_type']);

        if ($vote_type === 'like') {
            $current_likes = get_post_meta($quote_id, '_m2m_quote_likes', true) ?: 0;
            update_post_meta($quote_id, '_m2m_quote_likes', $current_likes + 1);
        } elseif ($vote_type === 'dislike') {
            $current_dislikes = get_post_meta($quote_id, '_m2m_quote_dislikes', true) ?: 0;
            update_post_meta($quote_id, '_m2m_quote_dislikes', $current_dislikes + 1);
        }
        wp_send_json_success();
    }
    wp_send_json_error();
}
add_action('wp_ajax_m2m_quotes_ver2_like_dislike', 'm2m_quotes_ver2_like_dislike');
add_action('wp_ajax_nopriv_m2m_quotes_ver2_like_dislike', 'm2m_quotes_ver2_like_dislike');

// Admin Settings Page
function m2m_quotes_ver2_admin_menu() {
    add_menu_page('M2M Quotes Ver2 Settings', 'M2M Quotes Ver2', 'manage_options', 'm2m-quotes-ver2', 'm2m_quotes_ver2_settings_page', 'dashicons-admin-generic', 'M2M Quotes Performance',
        'M2M Quotes Ver2',
        'manage_options',
        'm2m-quotes-ver2',
        'm2m_quotes_ver2_admin_page',
        'dashicons-chart-bar', 20);
}
add_action('admin_menu', 'm2m_quotes_ver2_admin_menu');


function m2m_quotes_ver2_admin_page() {
    ?>
    <div class="wrap">
        <h1>M2M Quotes Ver2 - Settings & Performance</h1>
        <h2 class="nav-tab-wrapper">
            <a href="#tab-performance" class="nav-tab nav-tab-active" data-tab="performance">Quote Performance</a>
            <a href="#tab-settings" class="nav-tab" data-tab="settings">Quote Settings</a>
            <a href="#tab-buttons" class="nav-tab" data-tab="buttons">Button URLs</a>
        </h2>

        <div id="tab-performance" class="tab-content">
            <?php m2m_quotes_ver2_display_quote_performance(); ?>
        </div>
        
        <div id="tab-settings" class="tab-content" style="display:none;">
            <?php m2m_quotes_ver2_display_settings(); ?>
        </div>

        <div id="tab-buttons" class="tab-content" style="display:none;">
            <?php m2m_quotes_ver2_display_button_urls(); ?>
        </div>
    </div>
    <?php
}

function m2m_quotes_ver2_settings_page() {
    ?>
    <div class="wrap">
        <h1>M2M Quotes Ver2 Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('m2m_quotes_ver2_settings_group');
            do_settings_sections('m2m-quotes-ver2-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Enqueue CSS and JavaScript for tabs in the admin
function m2m_quotes_ver2_admin_assets() {
    wp_enqueue_style('m2m-quotes-ver2-admin-css', plugins_url('/css/m2m-quotes-ver2.css', __FILE__));
    wp_enqueue_script('m2m-quotes-ver2-admin-js', plugins_url('/js/m2m-quotes-ver2.js', __FILE__), array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'm2m_quotes_ver2_admin_assets');

// Register Settings
function m2m_quotes_ver2_register_settings() {
    register_setting('m2m_quotes_ver2_settings_group', 'm2m_quotes_custom_button_1');
    add_settings_section('m2m_quotes_custom_buttons', 'Custom Buttons', null, 'm2m-quotes-ver2-settings');
    add_settings_field('m2m_quotes_custom_button_1', 'Button 1 URL', 'm2m_quotes_ver2_custom_button_callback', 'm2m-quotes-ver2-settings', 'm2m_quotes_custom_buttons', array('label_for' => 'm2m_quotes_custom_button_1'));
}
add_action('admin_init', 'm2m_quotes_ver2_register_settings');

function m2m_quotes_ver2_custom_button_callback($args) {
    $value = get_option($args['label_for']);
    echo '<input type="text" id="' . $args['label_for'] . '" name="' . $args['label_for'] . '" value="' . esc_attr($value) . '" />';
}

// Add custom columns to Quotes list in the admin
function m2m_quotes_ver2_add_custom_columns($columns) {
    $columns['likes'] = 'Likes';
    $columns['dislikes'] = 'Dislikes';
    return $columns;
}
add_filter('manage_m2m_quotes_posts_columns', 'm2m_quotes_ver2_add_custom_columns');

// Display custom column content
function m2m_quotes_ver2_custom_column_content($column, $post_id) {
    if ($column == 'likes') {
        echo get_post_meta($post_id, '_m2m_quote_likes', true) ?: '0';
    } elseif ($column == 'dislikes') {
        echo get_post_meta($post_id, '_m2m_quote_dislikes', true) ?: '0';
    }
}
add_action('manage_m2m_quotes_posts_custom_column', 'm2m_quotes_ver2_custom_column_content', 10, 2);

// Make Likes and Dislikes columns sortable
function m2m_quotes_ver2_sortable_columns($columns) {
    $columns['likes'] = 'likes';
    $columns['dislikes'] = 'dislikes';
    return $columns;
}
add_filter('manage_edit-m2m_quotes_sortable_columns', 'm2m_quotes_ver2_sortable_columns');


// Add a meta box to show quote performance on the edit quote page
function m2m_quotes_ver2_add_performance_meta_box() {
    add_meta_box('m2m_quotes_performance', 'Quote Performance', 'm2m_quotes_ver2_performance_meta_box_callback', 'm2m_quotes', 'side', 'default');
}
add_action('add_meta_boxes', 'm2m_quotes_ver2_add_performance_meta_box');

function m2m_quotes_ver2_performance_meta_box_callback($post) {
    $likes = get_post_meta($post->ID, '_m2m_quote_likes', true) ?: 0;
    $dislikes = get_post_meta($post->ID, '_m2m_quote_dislikes', true) ?: 0;
    echo '<p><strong>Likes:</strong> ' . esc_html($likes) . '</p>';
    echo '<p><strong>Dislikes:</strong> ' . esc_html($dislikes) . '</p>';
}

function m2m_quotes_ver2_display_quote_performance() {
    $quotes = new WP_Query(array('post_type' => 'm2m_quotes', 'posts_per_page' => -1));
    echo '<h3>Quote Performance</h3>';
    echo '<table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Quote</th>
                    <th>Likes</th>
                    <th>Dislikes</th>
                </tr>
            </thead>
            <tbody>';
    if ($quotes->have_posts()) {
        while ($quotes->have_posts()) {
            $quotes->the_post();
            $likes = get_post_meta(get_the_ID(), '_m2m_quote_likes', true) ?: 0;
            $dislikes = get_post_meta(get_the_ID(), '_m2m_quote_dislikes', true) ?: 0;
            echo '<tr>';
            echo '<td>' . get_the_content() . '</td>';
            echo '<td>' . $likes . '</td>';
            echo '<td>' . $dislikes . '</td>';
            echo '</tr>';
        }
    }
    wp_reset_postdata();
    echo '</tbody></table>';
}

// Register option for quote switching interval
function m2m_quotes_ver2_register_interval_setting() {
    register_setting('m2m_quotes_ver2_settings_group', 'm2m_quote_switch_interval');
}
add_action('admin_init', 'm2m_quotes_ver2_register_interval_setting');

function m2m_quotes_ver2_display_settings() {
    $interval = get_option('m2m_quote_switch_interval', 24);
    echo '<h3>Quote Switching Interval</h3>';
    echo '<form method="post" action="options.php">';
    settings_fields('m2m_quotes_ver2_settings_group');
    echo '<p><label for="m2m_quote_switch_interval">Switch interval (hours):</label> ';
    echo '<input type="number" id="m2m_quote_switch_interval" name="m2m_quote_switch_interval" value="' . esc_attr($interval) . '" min="1" max="168" /></p>';
    submit_button('Save Interval');
    echo '</form>';
}

// Register settings for customizable button URLs
function m2m_quotes_ver2_register_button_urls() {
    register_setting('m2m_quotes_ver2_buttons_group', 'm2m_button_url_1');
    register_setting('m2m_quotes_ver2_buttons_group', 'm2m_button_url_2');
    register_setting('m2m_quotes_ver2_buttons_group', 'm2m_button_url_3');
    register_setting('m2m_quotes_ver2_buttons_group', 'm2m_button_url_4');
}
add_action('admin_init', 'm2m_quotes_ver2_register_button_urls');

function m2m_quotes_ver2_display_button_urls() {
    echo '<h3>Button URLs</h3>';
    echo '<form method="post" action="options.php">';
    settings_fields('m2m_quotes_ver2_buttons_group');
    for ($i = 1; $i <= 4; $i++) {
        $url = get_option("m2m_button_url_$i", '');
        echo "<p><label for=\"m2m_button_url_$i\">Button $i URL:</label> ";
        echo "<input type=\"url\" id=\"m2m_button_url_$i\" name=\"m2m_button_url_$i\" value=\"" . esc_attr($url) . "\" /></p>";
    }
    submit_button('Save Button URLs');
    echo '</form>';
}

for ($i = 1; $i <= 4; $i++) {
    $button_url = get_option("m2m_button_url_$i", '#');
    if ($button_url) {
        echo "<a href=\"" . esc_url($button_url) . "\" target=\"_blank\" class=\"custom-button\">Button $i</a>";
    }
}
