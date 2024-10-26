<?php
/**
 * Plugin Name: M2M Quotes Ver2
 * Description: Display quotes that change every 24 hours, with analytics for likes and dislikes, customizable links, and sharing options.
 * Version: 2.2.1
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
    add_meta_box('m2m_quotes_performance', 'Quote Performance', 'm2m_quotes_ver2_performance_meta_box_callback', 'm2m_quotes', 'side', 'default');
}
add_action('add_meta_boxes', 'm2m_quotes_ver2_add_meta_boxes');

// Meta box callback for author and role fields
function m2m_quotes_ver2_meta_callback($post) {
    $author = get_post_meta($post->ID, '_m2m_quote_author', true);
    $role = get_post_meta($post->ID, '_m2m_quote_role', true);
    echo '<p><label for="m2m_quote_author">Author:</label> <input type="text" id="m2m_quote_author" name="m2m_quote_author" value="' . esc_attr($author) . '" /></p>';
    echo '<p><label for="m2m_quote_role">Role:</label> <input type="text" id="m2m_quote_role" name="m2m_quote_role" value="' . esc_attr($role) . '" /></p>';
}

// Meta box callback for quote performance
function m2m_quotes_ver2_performance_meta_box_callback($post) {
    $likes = get_post_meta($post->ID, '_m2m_quote_likes', true) ?: 0;
    $dislikes = get_post_meta($post->ID, '_m2m_quote_dislikes', true) ?: 0;
    echo '<p><strong>Likes:</strong> ' . esc_html($likes) . '</p>';
    echo '<p><strong>Dislikes:</strong> ' . esc_html($dislikes) . '</p>';
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

            // Display custom button URLs
            for ($i = 1; $i <= 4; $i++) {
                $button_url = get_option("m2m_button_url_$i", '#');
                if ($button_url) {
                    echo "<a href=\"" . esc_url($button_url) . "\" target=\"_blank\" class=\"custom-button\">Button $i</a>";
                }
            }

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

// AJAX handlers for like/dislike
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

// Add custom admin menu
function m2m_quotes_ver2_admin_menu() {
    add_menu_page(
        'M2M Quotes Ver2',
        'M2M Quotes Ver2',
        'manage_options',
        'm2m-quotes-ver2',
        'm2m_quotes_ver2_admin_page',
        'dashicons-chart-bar',
        20
    );
}
add_action('admin_menu', 'm2m_quotes_ver2_admin_menu');

// Admin page with tabs
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

// Quote Performance Tab Content
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

// Quote Interval Settings Tab Content
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

// Button URLs Tab Content
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

// Enqueue frontend and admin assets
function m2m_quotes_ver2_enqueue_assets() {
    wp_enqueue_style('m2m-quotes-ver2-css', plugins_url('/css/m2m-quotes-ver2.css', __FILE__));
    wp_enqueue_script('m2m-quotes-ver2-js', plugins_url('/js/m2m-quotes-ver2.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('m2m-quotes-ver2-js', 'm2m_quotes_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'm2m_quotes_ver2_enqueue_assets');
add_action('admin_enqueue_scripts', 'm2m_quotes_ver2_enqueue_assets');
