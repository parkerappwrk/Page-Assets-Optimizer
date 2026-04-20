<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class PageAssetsOptimizer_Frontend {
    
    private static $instance;
    
    public static function init() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'start_buffering'], 1);
        add_action('wp_enqueue_scripts', array($this, 'setupAssetRestrictions'), 999);
        add_action('template_redirect', array($this, 'custom_plugin_output_buffer_cleanup'), 999);
    }
    
    private function getPagePreferences($page_id) {
        global $wpdb;
        
        $query = $wpdb->prepare("SELECT preferences FROM {$wpdb->prefix}page_assets_selections WHERE page_id = %s", $page_id);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared above
        $preferences = $wpdb->get_var($query);
        
        return $preferences ? json_decode($preferences, true) : array();
    }
    
    public function setupAssetRestrictions() {
        global $post;
        
        if (is_admin() || !$post) {
            return;
        }
        
        $page_id = $post->ID;
        $preferences = $wpdb->get_var($wpdb->prepare("SELECT preferences FROM {$wpdb->prefix}page_assets_selections WHERE page_id = %s", $page_id));
        
        if (!empty($preferences)) {
            $preferences = json_decode($preferences, true);
            // Handle CSS restrictions
            if (!empty($preferences['styles_to_remove'])) {
                foreach ($preferences['styles_to_remove'] as $handle) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
            
            // Handle JS restrictions
            if (!empty($preferences['scripts_to_remove'])) {
                foreach ($preferences['scripts_to_remove'] as $handle) {
                    wp_dequeue_script($handle);
                    wp_deregister_script($handle);
                }
            }
            
            //Handle plugins restrictions
            if (!empty($preferences["plugins_to_remove"])) {
                $plugins_to_remove = $preferences["plugins_to_remove"];
            }
            
        }
    }

    public function start_buffering() {
        if (!is_admin()) {
            ob_start();
        }
    }

    public function custom_plugin_output_buffer_cleanup() {
        global $post;
        
        if (is_admin() || !$post) {
            return;
        }
        
        $page_id = $post->ID;
        $preferences = $wpdb->get_var($wpdb->prepare("SELECT preferences FROM {$wpdb->prefix}page_assets_selections WHERE page_id = %s", $page_id));
        
        if (!empty($preferences)) {
            $preferences = json_decode($preferences, true);
            ob_start(function ($html) use ($preferences) {
                if (!empty($preferences["scripts_to_remove_by_regex"])) {
                    foreach ($preferences["scripts_to_remove_by_regex"] as $script) {
                        $pattern = '/<script[^>]+src=["\'][^"\']*' . $script . '[^"\']*["\'][^>]*><\/script>/i';
                        $html = preg_replace($pattern, '', $html);
                    }
                }
                
                if (!empty($preferences["styles_to_remove_by_regex"])) {
                    foreach ($preferences["styles_to_remove_by_regex"] as $style) {
                        $pattern = '/<link[^>]+href=["\'][^"\']*' . $style . '[^"\']*["\'][^>]*>/i';
                        $html = preg_replace($pattern, '', $html);
                    }
                }
        
                return $html;
            });
        }
    }
}