<?php
/**
 * Frontend Controller for Page Assets Optimizer
 * Handles asset restriction based on saved preferences
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
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
        add_filter('the_content', array($this, 'convert_images_to_webp_or_avif'));
        add_filter('script_loader_src', array($this, 'custom_minify_js_css_url'), 10, 2);
        add_filter('style_loader_src', array($this, 'custom_minify_js_css_url'), 10, 2);
    }
    
    private function getPagePreferences($page_id) {
        global $wpdb, $wp_filesystem;
        
        $query = $wpdb->prepare("SELECT preferences FROM {$wpdb->prefix}page_assets_selections WHERE page_id = %s", $page_id);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared above
        $preferences = $wpdb->get_var($query);
        
        return $preferences ? json_decode($preferences, true) : array();
    }
    
    public function setupAssetRestrictions() {
        global $post, $wp_filesystem;

        if ( is_admin() || ! $post ) {
            return;
        }

        $preferences = array();
        $page_id     = $post->ID;

        // ✅ Safely get REQUEST_URI
        $request_uri = isset( $_SERVER['REQUEST_URI'] )
            ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
            : '';

        $parsed_url = ! empty( $request_uri ) ? wp_parse_url( $request_uri ) : array();

        $path     = isset( $parsed_url['path'] ) ? trim( $parsed_url['path'], '/' ) : '';
        $segments = ! empty( $path ) ? array_filter( explode( '/', $path ) ) : array();

        // First check full path
        if ( ! empty( $path ) ) {
            $preferences = $this->getPagePreferences( $path );
        }

        // If no preferences found for full path, check individual segments
        if ( empty( $preferences ) && ! empty( $segments ) ) {
            foreach ( $segments as $segment ) {
                $segment_prefs = $this->getPagePreferences( $segment );
                if ( ! empty( $segment_prefs ) ) {
                    $preferences = $segment_prefs;
                    break;
                }
            }
        }

        // Fallback to page ID if no segment matches found
        if ( empty( $preferences ) ) {
            $preferences = $this->getPagePreferences( $page_id );
        }

        if ( ! empty( $preferences ) ) {

            // Handle CSS restrictions
            if ( ! empty( $preferences['styles_to_remove'] ) ) {
                foreach ( $preferences['styles_to_remove'] as $handle ) {
                    wp_dequeue_style( $handle );
                    wp_deregister_style( $handle );
                }
            }

            // Handle JS restrictions
            if ( ! empty( $preferences['scripts_to_remove'] ) ) {
                foreach ( $preferences['scripts_to_remove'] as $handle ) {
                    wp_dequeue_script( $handle );
                    wp_deregister_script( $handle );
                }
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
        $preferences = $this->getPagePreferences($page_id);
        
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

    function convert_images_to_webp_or_avif( $content ) {
        global $wpdb;

        $table_name = esc_sql($wpdb->prefix . 'page_assets_optimization_prefs');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table, no user input
        $preferences = $wpdb->get_var(
            "SELECT image_optimization FROM {$table_name} LIMIT 1"
        );

        if ( $preferences !== '1' ) {
            return $content;
        }

        $accept = isset( $_SERVER['HTTP_ACCEPT'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) )
            : '';

        if ( strpos( $accept, 'image/webp' ) === false && strpos( $accept, 'image/avif' ) === false ) {
            return $content;
        }

        return preg_replace_callback(
            '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
            function ( $matches ) use ( $accept ) {

                $original_url = $matches[1];

                if (
                    ! preg_match( '/\.(jpe?g|png)$/i', $original_url ) ||
                    strpos( $original_url, '/uploads/' ) === false
                ) {
                    return $matches[0];
                }

                $upload_dir = wp_get_upload_dir();
                $image_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $original_url );

                $webp_path = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $image_path );
                $webp_url  = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $original_url );

                $avif_path = preg_replace( '/\.(jpe?g|png)$/i', '.avif', $image_path );
                $avif_url  = preg_replace( '/\.(jpe?g|png)$/i', '.avif', $original_url );
                
                if ( strpos( $accept, 'image/avif' ) !== false ) {
                    if ( ! file_exists( $avif_path ) && file_exists( $image_path ) ) {
                        $this->custom_convert_to_avif( $image_path, $avif_path );
                    }

                    if ( file_exists( $avif_path ) ) {
                        return str_replace( $original_url, $avif_url, $matches[0] );
                    }
                }
                
                if ( strpos( $accept, 'image/webp' ) !== false ) {
                    if ( ! file_exists( $webp_path ) && file_exists( $image_path ) ) {
                        $this->custom_convert_to_webp( $image_path, $webp_path );
                    }

                    if ( file_exists( $webp_path ) ) {
                        return str_replace( $original_url, $webp_url, $matches[0] );
                    }
                }

                return $matches[0];
            },
            $content
        );
    }
    
    function custom_convert_to_webp($source, $destination) {
        $info = getimagesize($source);
        $quality = 80;
    
        switch ($info['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            default:
                return false;
        }
    
        if ($image) {
            imagewebp($image, $destination, $quality);
            imagedestroy($image);
            return true;
        }
    
        return false;
    }
    
    function custom_convert_to_avif($source, $destination) {
        if (!class_exists('Imagick')) return false;
    
        try {
            $image = new Imagick($source);
            $image->setImageFormat('avif');
            $image->setImageCompressionQuality(80);
            $image->writeImage($destination);
            $image->clear();
            $image->destroy();
            log_error('AVIF conversion success');
            return true;
        } catch (Exception $e) {
            log_error('AVIF conversion failed: ' . $e->getMessage());
            return false;
        }
    }

    function custom_minify_js_css_url($src, $handle) {
        global $wpdb, $wp_filesystem;
        $table_name = $wpdb->prefix . 'page_assets_optimization_prefs';
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}page_assets_optimization_prefs");
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared above
        $preferences = $wpdb->get_results($query, ARRAY_A);

        if ($preferences[0]['minify_css'] != 1 && $preferences[0]['minify_js'] != 1) {
            return $src;
        }
        if (strpos($src, '/wp-content/') === false) {
            return $src;
        }
        
    
        $upload_dir = wp_get_upload_dir();
        $relative_path = strtok(wp_parse_url($src, PHP_URL_PATH), '?');
        $path = $_SERVER['DOCUMENT_ROOT'] . $relative_path;
        $ext = pathinfo($path, PATHINFO_EXTENSION);
    
        $min_path = preg_replace('/\.('.$ext.')$/', '.min.'.$ext, $path);
        $min_url  = preg_replace('/\.('.$ext.')$/', '.min.'.$ext, strtok($src, '?'));
    
        if (file_exists($min_path)) {
            return $min_url;
        }
    
        if (file_exists($path)) {
            $original = file_get_contents($path);    
            if ($ext === 'js' && $preferences[0]['minify_js'] == 1) {
                $minified = $this->custom_minify_js($original);
            } elseif ($ext === 'css' && $preferences[0]['minify_css'] == 1) {
                $minified = $this->custom_minify_css($original);
            }

            if (!$wp_filesystem->is_writable(dirname($min_path))) {
                log_error("Not writable: " . dirname($min_path));
            }
    
            if (!empty($minified)) {
                $written = file_put_contents($min_path, $minified);
                if ($written === false) {
                    log_error("Failed to write minified file to: $min_path");
                } else {
                    log_error("Minified file successfully written: $min_path");
                }
                return $min_url;
            } else {
                log_error("Minified content is empty for: " . $path);
            }
        }
    
        return $src;
    }

    function custom_minify_js($input) {
        $input = preg_replace('#//.*#', '', $input);
        $input = preg_replace('#/\*.*?\*/#s', '', $input);
        $input = preg_replace('/\s+/', ' ', $input);
        return trim($input);
    }
    
    function custom_minify_css($input) {
        $input = preg_replace('!/\*.*?\*/!s', '', $input);
        $input = preg_replace('/\s+/', ' ', $input);
        $input = str_replace([' {', '{ '], '{', $input);
        $input = str_replace([' }', '} '], '}', $input);
        $input = str_replace('; ', ';', $input);
        return trim($input);
    }
}

// Initialize
add_action('plugins_loaded', ['PageAssetsOptimizer_Frontend', 'init']);
