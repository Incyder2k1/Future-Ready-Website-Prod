<?php
/**
 * Plugin Name: Future-Ready Optimization
 * Description: Custom optimization tweaks for performance, SEO, and security.
 * Version: 1.0
 * Author: AI
 */

if (!defined('ABSPATH')) exit;

// =====================================================================
// 1. FRONTEND PERFORMANCE & SPEED
// =====================================================================

// Remove Emojis
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
add_filter('tiny_mce_plugins', function($plugins) {
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    }
    return array();
});

// Remove Query Strings from Static Resources (DISABLED - caused layout issues)
// add_filter('style_loader_src', 'fr_remove_cssjs_ver', 10, 2);
// add_filter('script_loader_src', 'fr_remove_cssjs_ver', 10, 2);
function fr_remove_cssjs_ver($src) {
    if (strpos($src, '?ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}

// Disable WP Embeds
function fr_disable_embeds_code_init() {
    remove_action('rest_api_init', 'wp_oembed_register_route');
    add_filter('embed_oembed_discover', '__return_false');
    remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
    add_filter('tiny_mce_plugins', 'fr_disable_embeds_tiny_mce_plugin');
    add_filter('rewrite_rules_array', 'fr_disable_embeds_rewrites');
    remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
}
add_action('init', 'fr_disable_embeds_code_init', 9999);
function fr_disable_embeds_tiny_mce_plugin($plugins) {
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpembed'));
    }
    return array();
}
function fr_disable_embeds_rewrites($rules) {
    if (is_array($rules)) {
        foreach($rules as $rule => $rewrite) {
            if(false !== strpos($rewrite, 'embed=true')) {
                unset($rules[$rule]);
            }
        }
    }
    return $rules;
}

// Defer Non-Essential JavaScript (DISABLED - broke the theme layout)
// add_filter('script_loader_tag', 'fr_defer_parsing_of_js', 10, 2);
function fr_defer_parsing_of_js($tag, $handle) {
    if (is_admin() || strpos($tag, 'defer') !== false) {
        return $tag;
    }
    // Don't defer jQuery to avoid breaking inline scripts
    $excludes = ['jquery', 'jquery-core', 'jquery-migrate'];
    if (in_array($handle, $excludes)) {
        return $tag;
    }
    return str_replace(' src', ' defer="defer" src', $tag);
}

// =====================================================================
// 2. SERVER & BACKEND PERFORMANCE
// =====================================================================

// Heartbeat API Control (limit to 60 seconds)
add_filter('heartbeat_settings', 'fr_heartbeat_settings');
function fr_heartbeat_settings($settings) {
    $settings['interval'] = 60;
    return $settings;
}

// Disable XML-RPC completely
add_filter('xmlrpc_enabled', '__return_false');

// =====================================================================
// 3. BASIC SEO & META DATA
// =====================================================================

// Clean Headings
remove_action('wp_head', 'rsd_link'); // EditURI link
remove_action('wp_head', 'wlwmanifest_link'); // Windows Live Writer
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0); // Shortlink
remove_action('wp_head', 'wp_generator'); // WP Version

// OpenGraph & Twitter Cards
add_action('wp_head', 'fr_add_opengraph_tags', 5);
function fr_add_opengraph_tags() {
    if (is_single() || is_page()) {
        global $post;
        if (!$post) return;
        $title = get_the_title();
        $desc = get_the_excerpt();
        if (empty($desc)) {
            $desc = wp_trim_words($post->post_content, 25, '...');
        }
        $url = get_permalink();
        $site_name = get_bloginfo('name');
        
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:type" content="article" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($desc) . '" />' . "\n";
        
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($desc) . '" />' . "\n";
        
        if (has_post_thumbnail()) {
            $img_src = get_the_post_thumbnail_url(get_the_ID(), 'large');
            echo '<meta property="og:image" content="' . esc_url($img_src) . '" />' . "\n";
            echo '<meta name="twitter:image" content="' . esc_url($img_src) . '" />' . "\n";
        }
    } else {
        $title = get_bloginfo('name');
        $desc = get_bloginfo('description');
        $url = home_url();
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($desc) . '" />' . "\n";
    }
}
