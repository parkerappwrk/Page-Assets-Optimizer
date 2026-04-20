<?php
if (!defined('ABSPATH')) exit;

// Only load in admin context
if (!is_admin()) {
    return;
}

class page_assets_optimizer_Settings {
    public function __construct() {
        // Ensure we're in admin context and WordPress is loaded
        if (!function_exists('add_settings_section')) {
            if (!defined('WP_ADMIN')) {
                require_once(ABSPATH . 'wp-admin/includes/admin.php');
            }
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        register_setting('pageAssetsOptimizer_settings', 'pageAssetsOptimizer_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
        
        add_settings_section(
            'pageAssetsOptimizer_main', 
            __('Main Settings', 'page-assets-optimizer'), 
            array($this, 'sectionCallback'), 
            'pageAssetsOptimizer'
        );
        
        add_action('admin_post_export_page_assets_settings', array($this, 'handleExport'));
        add_action('admin_post_import_page_assets_settings', array($this, 'handleImport'));
    }
    
    public function register() {
        // No code here
    }
    
    public function sectionCallback($args) {
        echo '<div class="col-12 d-flex flex-column pt-4">
            <h2>'.esc_html__('Import/Export Page Assets Optimizer Settings', 'page-assets-optimizer').'</h2>
            <p>'.esc_html__('Configure the main settings for Page Assets Optimizer.', 'page-assets-optimizer').'</p>
            <div class="col-12 d-flex gap-4">
                <div class="card d-flex flex-column gap-2 align-items-start">
                    <h4>'.esc_html__('Export Settings', 'page-assets-optimizer').'</h4>
                    <p class="mb-2 fs-6">'.esc_html__('Export all page assets optimizer settings as a JSON file. This will include all page assets optimizer settings and preferences.', 'page-assets-optimizer').'</p>
                    <form method="post" action="'.esc_url(admin_url('admin-post.php')).'" id="export-settings-form" class="mt-auto">
                        <input type="hidden" name="action" value="export_page_assets_settings">
                        '.wp_kses(
                            wp_nonce_field('export_page_assets_settings_nonce', 'export_nonce', true, false),
                            ['input' => ['type' => [], 'name' => [], 'id' => [], 'value' => []]]
                        ).'
                        <button type="submit" class="btn btn-primary px-3">'.esc_html__('Export Data', 'page-assets-optimizer').'</button>
                    </form>
                </div>

                <div class="card">
                    <h4>'.esc_html__('Import Settings', 'page-assets-optimizer').'</h4>
                    <p class="mb-2 fs-6">'.esc_html__('Import page assets optimizer settings from a JSON file. This will override all existing settings.', 'page-assets-optimizer').'</p>
                    <form action="'.esc_url(admin_url('admin-post.php')).'" method="post" enctype="multipart/form-data" id="import-settings-form">
                        <div class="mb-3">
                            <label for="import_file" class="form-label"></label>
                            <input class="form-control px-2" type="file" name="import_file" accept=".json" required>
                        </div>
                        <input type="hidden" name="action" value="import_page_assets_settings">
                        '.wp_kses(
                            wp_nonce_field('import_page_assets_settings_nonce', 'import_nonce', true, false),
                            ['input' => ['type' => [], 'name' => [], 'id' => [], 'value' => []]]
                        ).'
                        <button type="submit" class="btn btn-success px-3">'.esc_html__('Import Data', 'page-assets-optimizer').'</button>
                    </form>
                </div>
            </div>
        </div>';
    }
    
    public function sanitize_options($input) {
        // Add your sanitization logic here
        return $input;
    }
    
    public function handleExport() {
        if (!isset($_POST['export_nonce']) || !wp_verify_nonce($_POST['export_nonce'], 'export_page_assets_settings_nonce')) {
            wp_die(esc_html__('Invalid nonce.', 'page-assets-optimizer'));
        }
        
        global $wpdb;
        
        $results = array_map(function($row) {
            unset($row['created_at'], $row['updated_at']);
            return $row;
        }, $wpdb->get_results("SELECT * FROM {$wpdb->prefix}page_assets_selections", ARRAY_A));
        
        $prefs_results = array_map(function($row) {
            unset($row['created_at'], $row['updated_at']);
            return $row;
        }, $wpdb->get_results("SELECT * FROM {$wpdb->prefix}page_assets_optimization_prefs", ARRAY_A));
        
        $export_package = [
            'version' => '1.0',
            'date' => gmdate('Y-m-d H:i:s'),
            'page_assets_selections' => $results,
            'page_assets_optimization_prefs' => $prefs_results
        ];
        
        $export_data = json_encode($export_package, JSON_PRETTY_PRINT);
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="page_assets_optimizer_settings_' . gmdate("Y-m-d") . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $export_data;
        exit;
    }
    
    public function handleImport() {
        if (!isset($_POST['import_nonce']) || !wp_verify_nonce($_POST['import_nonce'], 'import_page_assets_settings_nonce')) {
            wp_die(esc_html__('Invalid nonce.', 'page-assets-optimizer'));
        }
        
        if (empty($_FILES['import_file']['tmp_name'])) {
            wp_die(esc_html__('Please upload a file to import.', 'page-assets-optimizer'));
        }
        
        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $import_package = json_decode($file_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die(esc_html__('Invalid JSON file.', 'page-assets-optimizer'));
        }

        global $wpdb;
        $imported = 0;
        
        // Handle page_assets_selections import
        if (isset($import_package['page_assets_selections'])) {
            $selections_table = $wpdb->prefix . 'page_assets_selections';
            $wpdb->query("TRUNCATE TABLE {$selections_table}");
            
            foreach ($import_package['page_assets_selections'] as $row) {
                if (!isset($row['page_id']) || !isset($row['preferences'])) {
                    continue;
                }
                
                $wpdb->insert($selections_table, $row);
                $imported++;
            }
        }
        
        // Handle page_assets_optimization_prefs import
        if (isset($import_package['page_assets_optimization_prefs'])) {
            $prefs_table = $wpdb->prefix . 'page_assets_optimization_prefs';
            $wpdb->query("TRUNCATE TABLE {$prefs_table}");
            
            foreach ($import_package['page_assets_optimization_prefs'] as $row) {
                $wpdb->insert($prefs_table, $row);
                $imported++;
            }
        }
        
        wp_redirect(admin_url('admin.php?page=settings&imported='.$imported));
        echo '<script>
            showToast("Settings imported successfully!", "success");
        </script>';
        exit;
    }
}
