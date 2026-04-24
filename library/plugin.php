<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
class page_assets_optimizer_plugin extends pageAssetsOptimizer_shortcode
{
    public $noticeOnPage  = 0;
    public $logError      = false;
	public $errorMessage      ='';
	public $subMenu       = array();
    public $adminDashboard;
    public $adminSettings;
    public $adminOptimizer;
    public $tablePreferences = 'page_assets_preferences';
    public $tableSelections = 'page_assets_selections';
    const DB_VERSION = 2; // Increment when schema changes
    const DB_VERSION_OPTION = 'page_assets_optimizer_db_version';
    /**
     * Plugin version
     * @var string
     */
    public $version;

    public static function init()
    {
        new self;
    }

    public function __construct()
    {
        $this->version = '1.0.0'; // Initialize version
        
        parent::__construct();
        
        $this->subMenu = array(
            "optimizer" => "Page Optimizer",
            "settings" => "Settings"
        );
        
        // Only initialize admin features in admin context
        if (is_admin()) {
            $settingsPreferences = $this->getSettingsPreferences();
            // Initialize admin components first
            $this->adminDashboard = new page_assets_optimizer_Dashboard($settingsPreferences);
            $this->adminSettings = new page_assets_optimizer_Settings();
            $this->adminOptimizer = new page_assets_optimizer_Optimizer();
            
            // Then register hooks
            add_action('admin_menu', array($this, 'createAdminMenu'));
            
            // Register settings after menu is set up
            add_action('admin_init', function() {
                $this->adminSettings->register();
            });
            
            add_action('wp_ajax_page_assets_optimize', array($this, 'handleOptimization'));
            
            // Add AJAX handler for getting page assets
            add_action('wp_ajax_page_assets_get_assets', array($this, 'getPageAssets'));
            
            // Add AJAX handler for saving preferences
            add_action('wp_ajax_page_assets_save_preferences', array($this, 'saveAssetPreferences'));
            
            // Add AJAX handler for getting existing preferences
            add_action('wp_ajax_page_assets_get_preferences', array($this, 'getAssetPreferences'));
            
            // Add test endpoint
            add_action('wp_ajax_page_assets_test_db_connection', array($this, 'testDbConnection'));
            
            add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));

            add_action('wp_ajax_page_assets_optimizer_get_assets', array($this, 'getAssetsForPage'));
            
            add_action('wp_ajax_page_assets_save_image_optimization', array($this, 'saveImageOptimization'));

            add_action('wp_ajax_page_assets_save_minify', array($this, 'saveMinify'));

            add_action('wp_ajax_page_assets_get_segment_list', array($this, 'getSegmentList'));
        }
        
        // Hook to prevent loading of disabled assets
        add_action('wp_enqueue_scripts', array($this, 'maybeDisableAssets'), 9999);
    }
    
	public function ptpfrw_restrict_manager_access_to_plugins_page()
    {
	// Check if the user is logged in, has the 'manager' role, and is trying to access plugins.php
        if (is_user_logged_in() && current_user_can('manager') && strpos($_SERVER['REQUEST_URI'], 'plugins.php') !== false) {
            // Redirect the user away from plugins.php to the dashboard
            wp_safe_redirect(admin_url());
            exit;
        }
	}
	// Remove the Plugins menu for users with the custom role
	public function ptpfrw_remove_plugins_menu_for_custom_role() 
    {
		if (current_user_can('manager')) {
			remove_menu_page('plugins.php');
		}
	}

    function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return;
        }
        $type = $this->FriendlyErrorType($errno);
        $this->errorMessage .= "Error: [" . $type . "] $errstr in $errfile on line number $errline\n";

        /* Don't execute PHP internal error handler */
        return true;
    }

    public function handleErrors()
    {
        $error = error_get_last();

        # Checking if last error is a fatal error
        if (($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR)) {
            # Here we handle the error, displaying HTML, logging, ...
            $type = $this->FriendlyErrorType($error['type']);
            $this->errorMessage .= "Error: [" . $type . "] " . $error['message'] . " in " . $error['file'] . " on line number " . $error['line'];
            $result["success"] = false;
            $result["message"] = $this->errorMessage;
            header('content-type: application/json');
            $response = $result;
            echo json_encode($response);
            die();
        } else if ($error['type'] != "") {
            $type = $this->FriendlyErrorType($error['type']);
            $this->errorMessage .= "Error: [" . $type . "] " . $error['message'] . " in " . $error['file'] . " on line number " . $error['line'];
        }
    }

    public function FriendlyErrorType($type)
    {
        switch ($type) {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }
        return "";
    }

    public static function ptpfrw_activate()
    {
        $notices = get_option('_ptpfrw_admin_notices', array());
        $indexedPosts = get_option('_ptpfrw_indexed_posts');
        if ($indexedPosts != "" && $indexedPosts != 0 && $indexedPosts != false) {
            $syncStatus = self::getSyncStatus();
            if ($syncStatus) {
                $msg = self::admin_reindex_messages();
                if (!self::checkNotices($notices, "recommended to")) {
                    $notices[] = $msg;
                }
            }
        } else {
            $msg = self::admin_notice_messages();
            if (!self::checkNotices($notices, "been activated")) {
                $notices[] = $msg;
            }
        }
        update_option('_ptpfrw_admin_notices', $notices);
        
        // Create instance to call non-static createTables method
        $instance = new self();
        $instance->createTables();
        $instance->maybeUpdateTables();
    }

    public static function checkNotices($notices, $word)
    {
        if (count($notices) > 0) {
            foreach ($notices as $k => $v) {
                if (strpos($v, $word) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    public function createTables()
    {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}page_assets_selections (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            preferences longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY page_id (page_id)
        ) ENGINE=InnoDB {$charset_collate};";
        
        dbDelta($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}page_assets_optimization_prefs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            image_optimization tinyint(1) DEFAULT 1,
            minify_css tinyint(1) DEFAULT 1,
            minify_js tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY page_id (page_id)
        ) $charset_collate;";

        dbDelta($sql);
        
        // Set default option
        add_option('page_assets_image_optimization', 0);
        add_option('page_assets_minify_css', 0);
        add_option('page_assets_minify_js', 0);
    }

    protected function maybeUpdateTables() {
        $current_version = get_option(self::DB_VERSION_OPTION, 0);
        
        if ($current_version < self::DB_VERSION) {
            $this->updateTables($current_version);
            update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        }
    }
    
    protected function updateTables( $current_version ) {
        global $wpdb;

        if ( $current_version < 2 ) {

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries and cannot be cached
            $wpdb->query( 'START TRANSACTION' );

            try {

                // Create or update tables
                $this->createTables();

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries
                $wpdb->query( 'COMMIT' );

            } catch ( Exception $e ) {

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries
                $wpdb->query( 'ROLLBACK' );
            }
        }
    }

    public function get_user_role()
    {
        global $current_user;
    
        $user_roles = $current_user->roles;
        $user_role = array_shift($user_roles);
    
        return $user_role;
    }

    public function check_slug_exists($post_name)
    {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT post_name FROM {$wpdb->posts} WHERE post_name = %s",
            $post_name
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above using $wpdb->prepare()
        $result = $wpdb->get_row($query, ARRAY_A);

        return !empty($result);
    }

    public static function admin_notice_messages()
    {
        return "";
    }

    public function pageAssetsOptimizer_admin_init()
    {
        global $ptpfrwAPIClient;
        $current_version = PAGE_ASSETS_OPTIMIZER_PLUGIN_VERSION;
    }

    function pageAssetsOptimizer_admin_notices()
    {
        $notices = get_option('_pageAssetsOptimizer_admin_notices', array());

        if (count($notices) > 0) {
            foreach ($notices as $notice) {
                echo '<div class="update-nag pageAssetsOptimizer-notices">'.esc_html($notice).'</div>';
            }
            delete_option('_pageAssetsOptimizer_admin_notices');
        }
    }

    public function getDomain()
    {
        $domain         = get_option('siteurl');
        $find           = array('http://','https://');
        $replace        = array('','');
        $domain         = str_replace($find, $replace, $domain);
        $this->domain   = strtolower($domain);
        return $this->domain;
    }

    public function createAdminMenu()
    {
        add_menu_page(
            __('Page Assets Optimizer', 'page-assets-optimizer'), 
            __('Page Assets Optimizer', 'page-assets-optimizer'), 
            $this->lqMenuCapabilities, 
            'pageAssetsOptimizer', 
            array($this->adminDashboard, 'display'), 
            esc_url(PAGE_ASSETS_OPTIMIZER_BASE_URL . '/assets/images/logo.png?v=2')
        );
        
        foreach($this->subMenu as $slug => $menu) {
            $callback = ($slug === 'settings') 
                ? array($this->adminSettings, 'sectionCallback')
                : array($this->adminOptimizer, 'display');
                
            add_submenu_page(
                'pageAssetsOptimizer',
                $menu === 'optimizer' ? __('Optimizer', 'page-assets-optimizer') : __('Settings', 'page-assets-optimizer'),
                $menu === 'optimizer' ? __('Optimizer', 'page-assets-optimizer') : __('Settings', 'page-assets-optimizer'),
                $this->lqMenuCapabilities, 
                $slug, 
                $callback
            );
        }
    }

    public function registerSettings() {
        register_setting('pageAssetsOptimizer_settings', 'pageAssetsOptimizer_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
        
        // Add settings sections
        add_settings_section(
            'pageAssetsOptimizer_main', 
            __('Main Settings', 'page-assets-optimizer'), 
            array($this, 'settingsSectionCallback'), 
            'pageAssetsOptimizer'
        );
        
        // Add settings fields here as needed
    }

    public function settingsSectionCallback($args) {
        // Section callback content
        echo '<p>'.esc_html__('Configure the main settings for Page Assets Optimizer.', 'page-assets-optimizer').'</p>';
    }

    public function dashboard() {
        // Main dashboard content
        echo '<div class="wrap">';
        echo '<h1>'.esc_html__('Page Assets Optimizer Dashboard', 'page-assets-optimizer').'</h1>';
        echo '<p>'.esc_html__('Welcome to Page Assets Optimizer. Use this plugin to optimize your page assets.', 'page-assets-optimizer').'</p>';
        echo '</div>';
    }

    public function manageSubmenuItems() {
        // Handle submenu page content based on current screen
        $screen = get_current_screen();
        
        echo '<div class="wrap">';
        echo '<h1>'.esc_html($this->subMenu[$_GET['page']]).'</h1>';
        
        switch($_GET['page']) {
            case 'settings':
                settings_fields('pageAssetsOptimizer_settings');
                do_settings_sections('pageAssetsOptimizer');
                submit_button();
                break;
            case 'optimizer':
                // Optimizer functionality will go here
                break;
        }
        
        echo '</div>';
    }

    public function handleOptimization() {
        check_ajax_referer('page_assets_optimizer', 'nonce');
        
        $type = $_POST['type'] ?? '';
        $options = $_POST['options'] ?? array();
        
        try {
            switch($type) {
                case 'css':
                    $result = $this->optimizeCSS();
                    break;
                case 'js':
                    $result = $this->optimizeJS();
                    break;
                case 'image':
                    $result = $this->optimizeImages($options);
                    break;
                default:
                    throw new Exception(esc_html__('Invalid optimization type', 'page-assets-optimizer'));
            }
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error(['message' => esc_html__('Operation failed', 'page-assets-optimizer') . ': ' . esc_html($e->getMessage())], 500);
        }
    }

    protected function optimizeCSS() {
        // CSS optimization logic
        return array('message' => 'CSS optimized successfully');
    }

    protected function optimizeJS() {
        // JS optimization logic
        return array('message' => 'JavaScript optimized successfully');
    }

    protected function optimizeImages($options) {
        // Image optimization logic
        return array(
            'message' => 'Images optimized',
            'options' => $options
        );
    }

    public function getPageAssets() {
        check_ajax_referer('page_assets_optimizer', 'nonce');
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        
        if (!$page_id) {
            wp_send_json_error('Invalid page ID');
        }

        try {
            $this->setupPageEnvironment($page_id);
            global $wp_styles, $wp_scripts;
            $assets = array(
                'css' => $this->filterAssets($wp_styles),
                'js'  => $this->filterAssets($wp_scripts)
            );
    
            wp_send_json_success($assets);
        } catch (Exception $e) {
            wp_send_json_error(['message' => esc_html__('Operation failed', 'page-assets-optimizer') . ': ' . esc_html($e->getMessage())], 500);
        }
    }

    protected function filterAssets($global_obj) {
        $assets = [];
    
        // Gather enqueued assets (those actually used on page)
        foreach ($global_obj->queue as $handle) {
            if (!empty($global_obj->registered[$handle]->src) && !$this->isAdminAsset($global_obj->registered[$handle]->src)) {
                $assets[] = [
                    'handle' => $handle,
                    'src'    => $global_obj->registered[$handle]->src
                ];
            }
        }
    
        // Optionally add registered-but-not-enqueued (if needed)
        foreach ($global_obj->registered as $handle => $obj) {
            if (!in_array($handle, $global_obj->queue) && !empty($obj->src) && !$this->isAdminAsset($obj->src)) {
                $assets[] = [
                    'handle' => 'no-handle',
                    'src'    => $obj->src
                ];
            }
        }
    
        return $assets;
    }

    protected function isAdminAsset($src) {
        return strpos($src, '/wp-admin/') !== false || strpos($src, 'load-scripts.php') !== false || strpos($src, 'load-styles.php') !== false;
    }
    
    protected function getEnqueuedStyles($page_id) {
        global $wp_styles;
        $this->setupPageEnvironment($page_id);
        
        $styles = array();
        
        foreach ($wp_styles->queue as $handle) {
            if (isset($wp_styles->registered[$handle]) && 
                !empty($wp_styles->registered[$handle]->src) &&
                strpos($wp_styles->registered[$handle]->src, '/wp-admin/') === false) {
                $styles[] = array(
                    'handle' => $handle,
                    'src' => $wp_styles->registered[$handle]->src
                );
            }
        }
        
        foreach ($wp_styles->registered as $handle => $style) {
            if (!in_array($handle, $wp_styles->queue) && 
                !empty($style->src) && 
                strpos($style->src, '/wp-admin/') === false) {
                $styles[] = array(
                    'handle' => 'no-handle',
                    'src' => $style->src
                );
            }
        }
        
        return $styles;
    }
    
    protected function getEnqueuedScripts($page_id) {
        global $wp_scripts;
        $this->setupPageEnvironment($page_id);
        
        $scripts = array();
        
        foreach ($wp_scripts->queue as $handle) {
            if (isset($wp_scripts->registered[$handle]) && 
                !empty($wp_scripts->registered[$handle]->src) &&
                strpos($wp_scripts->registered[$handle]->src, '/wp-admin/') === false) {
                $scripts[] = array(
                    'handle' => $handle,
                    'src' => $wp_scripts->registered[$handle]->src
                );
            }
        }
        
        foreach ($wp_scripts->registered as $handle => $script) {
            if (!in_array($handle, $wp_scripts->queue) && 
                !empty($script->src) && 
                strpos($script->src, '/wp-admin/') === false) {
                $scripts[] = array(
                    'handle' => 'no-handle',
                    'src' => $script->src
                );
            }
        }
        
        return $scripts;
    }

    protected function setupPageEnvironment($page_id) {
        // Setup the environment as if we're loading the page
        global $post;
        $post = get_post($page_id);
        setup_postdata($post);
        
        // Trigger WordPress to register scripts/styles
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function getAssetPreferences() {
        check_ajax_referer('page_assets_optimizer', 'nonce');
        
        $page_id = isset($_REQUEST['page_id']) ? $_REQUEST['page_id'] : null;
        
        global $wpdb;
        
        try {
            if ($page_id) {
                $cache_key   = 'page_assets_pref_' . $page_id;
                $cache_group = 'page_assets_optimizer';

                $preferences = wp_cache_get($cache_key, $cache_group);

                // Get preferences for specific page
                $result = $wpdb->get_row($wpdb->prepare(
                    "SELECT preferences FROM {$wpdb->prefix}page_assets_selections WHERE page_id = %d",
                    $page_id
                ));
                
                $preferences = $result ? json_decode($result->preferences, true) : [];
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    /* translators: %s: JSON error message */
                    throw new Exception(sprintf(esc_html__('JSON decode error: %s', 'page-assets-optimizer'), esc_html(json_last_error_msg())));
                }
                
                wp_send_json_success($preferences);
            } else {
                // Fallback: get all preferences (for backward compatibility)
                $results = pao_get_cached_query('page_assets_all_preferences', "SELECT page_id, preferences FROM {$wpdb->prefix}page_assets_selections", 'results');

                $all_preferences = [];

                if (!empty($results)) {
                    foreach ($results as $row) {
                        $all_preferences[$row->page_id] = json_decode($row->preferences, true);
                    }
                }

                wp_send_json_success($all_preferences);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => esc_html__('Operation failed', 'page-assets-optimizer') . ': ' . esc_html($e->getMessage())], 500);
        }
    }

    public function saveAssetPreferences() {
        global $wpdb;
        
        try {
            // Force display errors temporarily
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
            
            // Test direct query
            $test = $wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}page_assets_selections'");
            if (!$test) {
                throw new Exception(esc_html__('Table does not exist', 'page-assets-optimizer'));
            }
            
            check_ajax_referer('page_assets_optimizer', 'nonce');
            
            if (!isset($_POST['optimization_data'])) {
                throw new Exception(esc_html__('Missing optimization data', 'page-assets-optimizer'));
            }
            
            log_error('Received raw optimization data: ' . print_r($_POST['optimization_data'], true));
            
            $data = stripslashes($_POST['optimization_data']);
            $optimization_data = json_decode($data, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                /* translators: %s: JSON error message */
                throw new Exception(sprintf(esc_html__('JSON decode error: %s', 'page-assets-optimizer'), esc_html(json_last_error_msg())));
            }
            
            log_error('Decoded optimization data: ' . print_r($optimization_data, true));
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries and cannot be cached
            $wpdb->query('START TRANSACTION');
            
            foreach ($optimization_data as $page_id => $preferences) {
                if ((!is_numeric($page_id) && !is_string($page_id)) || empty($page_id)) {
                    throw new Exception(esc_html(sprintf('Invalid page ID or URL segment: %s', $page_id), 'page-assets-optimizer'));
                }
                
                $preferences = $this->validatePreferences($preferences);
                
                log_error('Saving preferences for page ' . $page_id . ': ' . print_r($preferences, true));
                
                $result = $wpdb->replace(
                    $wpdb->prefix.'page_assets_selections',
                    [
                        'page_id' => $page_id,
                        'preferences' => wp_json_encode($preferences),
                        'updated_at' => gmdate('Y-m-d H:i:s')
                    ]
                );
                
                if ($result === false) {
                    /* translators: %s: Database error message */
                    throw new Exception(sprintf(esc_html__('Database error: %s', 'page-assets-optimizer'), esc_html($wpdb->last_error)));
                }
            }
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries
            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => 'Preferences saved successfully']);
            
        } catch (Exception $e) {
            if (isset($wpdb)) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries
                $wpdb->query('ROLLBACK');
            }
            log_error('Save error: ' . $e->getMessage());
            wp_send_json_error(['message' => esc_html__('Operation failed', 'page-assets-optimizer') . ': ' . esc_html($e->getMessage())], 500);
        }
    }
    
    protected function validatePreferences($preferences) {
        $defaults = [
            'scripts_to_remove' => [],
            'styles_to_remove' => [],
            'scripts_to_remove_by_regex' => [],
            'styles_to_remove_by_regex' => []
        ];
        
        // Ensure all required keys exist
        $preferences = wp_parse_args($preferences, $defaults);
        
        // Validate array types
        foreach ($defaults as $key => $value) {
            if (!is_array($preferences[$key])) {
                return new WP_Error('invalid_type', esc_html(sprintf('Invalid type: %s must be an array', $key)));
            }
        }
        
        return $preferences;
    }
    
    public function getAssetsForPage() {
        if (!is_user_logged_in()) return;
        
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        global $wp_scripts, $wp_styles;
    
        // Force WordPress to resolve all enqueued scripts/styles
        ob_start();
        wp_print_scripts();
        wp_print_styles();
        ob_end_clean();
    
        $htmlJS = '<div class="asset-list-header position-sticky top-0 z-index-2 p-3">
                <h5>JS Assets</h5>
                <input type="text" class="asset-search form-control" placeholder="Search JS files..." data-asset-type="js"/>
            </div>
            <div class="asset-list">
                <ul>';
        $scripts = [];
        
        $jsCount = 0;
        foreach ($wp_scripts->done as $handle) {
            $script = $wp_scripts->registered[$handle] ?? null;
            if ($script && !empty($script->src) && strpos($script->src, '/wp-admin/') === false) {
                $scripts[] = ['handle' => $handle, 'src' => $script->src];
                $htmlJS .= '<li class="asset-list-row" data-src="'.$script->src.'" data-handle="'.$handle.'">
                    <input id="js_'.$jsCount.'_assets" type="checkbox" name="js_assets[]" value="'.$script->src.'">
                    <label for="js_'.$jsCount.'_assets">
                        <div class="asset-info d-flex flex-column"><span class="asset-handle">' . esc_html($handle) . '</span><span class="asset-src">' . esc_url($script->src) . '</span></div>
                    </label>
                </li>';
            }
            $jsCount++;
        }
        
        foreach ($wp_scripts->registered as $handle => $script) {
            if (!in_array($handle, $wp_scripts->queue) && 
                !empty($script->src) && 
                strpos($script->src, '/wp-admin/') === false) {
                $scripts[] = ['handle' => 'no-handle', 'src' => $script->src];
                $htmlJS .= '<li class="asset-list-row" data-src="'.$script->src.'" data-handle="no-handle">
                    <input id="js_'.$jsCount.'_assets" type="checkbox" name="js_assets[]" value="'.$script->src.'">
                    <label for="js_'.$jsCount.'_assets">
                        <div class="asset-info d-flex flex-column"><span class="no-handle">no-handle</span><span class="asset-src">' . esc_url($script->src) . '</span></div>
                    </label>
                </li>';
            }
            $jsCount++;
        }
    
        $htmlJS .= '</ul></div>';
        $styles = [];
        
        $htmlCSS = '<div class="asset-list-header position-sticky top-0 z-index-2 p-3">
                <h5>CSS Assets</h5>
                <input type="text" class="asset-search form-control" placeholder="Search CSS files..." data-asset-type="css"/>
            </div>
            <div class="asset-list">
                <ul>';
        $cssCount = 0;
        foreach ($wp_styles->done as $handle) {
            $style = $wp_styles->registered[$handle] ?? null;
            if ($style && !empty($style->src) && strpos($style->src, '/wp-admin/') === false) {
                $styles[] = ['handle' => $handle, 'src' => $style->src];
                $htmlCSS .= '<li class="asset-list-row" data-src="'.$style->src.'" data-handle="'.$handle.'">
                    <input id="css_'.$cssCount.'_assets" type="checkbox" name="css_assets[]" value="'.$style->src.'">
                    <label for="css_'.$cssCount.'_assets">
                        <div class="asset-info d-flex flex-column"><span class="asset-handle">' . esc_html($handle) . '</span><span class="asset-src">' . esc_url($style->src) . '</span></div>
                    </label>
                </li>';
            }
            $cssCount++;
        }
        
        foreach ($wp_styles->registered as $handle => $style) {
            if (!in_array($handle, $wp_styles->queue) && 
                !empty($style->src) && 
                strpos($style->src, '/wp-admin/') === false) {
                $styles[] = ['handle' => 'no-handle', 'src' => $style->src];
                $htmlCSS .= '<li class="asset-list-row" data-src="'.$style->src.'" data-handle="no-handle">
                    <input id="css_'.$cssCount.'_assets" type="checkbox" name="css_assets[]" value="'.$style->src.'">
                    <label for="css_'.$cssCount.'_assets">
                        <div class="asset-info d-flex flex-column"><span class="no-handle">no-handle</span><span class="asset-src">' . esc_url($style->src) . '</span></div>
                    </label>
                </li>';
            }
            $cssCount++;
        }
        $htmlCSS .= '</ul></div>';

        $html = array(
            'css' => $htmlCSS,
            'js' => $htmlJS
        );

        wp_send_json_success($html);
    }
    

    public function testDbConnection() {
        global $wpdb;
        
        try {
            $test_data = ['test' => 'value'];
            
            $result = $wpdb->insert(
                $wpdb->prefix.'page_assets_selections',
                [
                    'page_id' => 999,
                    'preferences' => json_encode($test_data)
                ]
            );
            
            if ($result === false) {
                /* translators: %s: Database error message */
                throw new Exception(sprintf(esc_html__('Database error: %s', 'page-assets-optimizer'), esc_html($wpdb->last_error)));
            }
            
            wp_send_json_success(['message' => 'Test data inserted successfully']);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => esc_html__('Operation failed', 'page-assets-optimizer') . ': ' . esc_html($e->getMessage())], 500);
        }
    }

    public function maybeDisableAssets() {
        if (is_admin()) return;
        
        global $wpdb, $wp_styles, $wp_scripts;
        
        $page_id = get_the_ID();
        if (!$page_id) return;
        
        // Get preferences for this page
        $preferences = $wpdb->get_row($wpdb->prepare(
            "SELECT preferences FROM {$this->tableSelections} WHERE page_id = %d",
            $page_id
        ));
        
        if ($preferences) {
            $preferences = json_decode($preferences->preferences, true);
            
            if (isset($preferences[$page_id])) {
                $config = $preferences[$page_id];
                
                // Handle direct asset removal
                if (!empty($config['styles_to_remove'])) {
                    foreach ($config['styles_to_remove'] as $handle) {
                        wp_dequeue_style($handle);
                    }
                }
                
                if (!empty($config['scripts_to_remove'])) {
                    foreach ($config['scripts_to_remove'] as $handle) {
                        wp_dequeue_script($handle);
                    }
                }
                
                // Handle regex patterns
                if (!empty($config['styles_to_remove_by_regex'])) {
                    foreach ($wp_styles->registered as $handle => $style) {
                        foreach ($config['styles_to_remove_by_regex'] as $pattern) {
                            if (isset($style->src) && preg_match("/{$pattern}/", $style->src)) {
                                wp_dequeue_style($handle);
                            }
                        }
                    }
                }
                
                if (!empty($config['scripts_to_remove_by_regex'])) {
                    foreach ($wp_scripts->registered as $handle => $script) {
                        foreach ($config['scripts_to_remove_by_regex'] as $pattern) {
                            if (isset($script->src) && preg_match("/{$pattern}/", $script->src)) {
                                wp_dequeue_script($handle);
                            }
                        }
                    }
                }
            }
        }
    }

    public function enqueueAdminAssets() {
        if (!is_admin() || !current_user_can('administrator')) {
            return;
        }
        
        // Verify assets exist
        $css_path = plugin_dir_path(__FILE__) . '../assets/css/admin.css';
        $js_path = plugin_dir_path(__FILE__) . '../assets/js/admin.js';
        
        if (!file_exists($css_path) || !file_exists($js_path)) {
            log_error('Page Assets Optimizer: Missing asset files');
            return;
        }
        
        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap-css',
            plugins_url('../assets/css/bootstrap.min.css', __FILE__),
            array(),
            '5.3.0'
        );
        
        // Bootstrap JS Bundle (includes Popper)
        wp_enqueue_script(
            'bootstrap-js',
            plugins_url('../assets/js/bootstrap.bundle.min.js', __FILE__),
            array('jquery'),
            '5.3.0',
            true
        );
        
        // Plugin assets - using correct relative paths
        wp_enqueue_style(
            'page-assets-optimizer-admin-css',
            plugins_url('../assets/css/admin.css', __FILE__),
            array('admin-css'),
            $this->version
        );
        
        wp_enqueue_script(
            'page-assets-optimizer-admin-js',
            plugins_url('../assets/js/admin.js', __FILE__),
            array('jquery', 'admin-js'),
            $this->version,
            true
        );
        
        wp_enqueue_script(
            'page-assets-optimizer-quicksearch-js',
            plugins_url('../assets/js/backend/jquery.quicksearch.js', __FILE__),
            array('jquery'),
            $this->version,
            true
        );
    }
    
    public function saveImageOptimization() {        
        if (!current_user_can('administrator')) {
            wp_send_json_error(__('Unauthorized', 'page-assets-optimizer'));
            return;
        }
        check_ajax_referer('page_assets_optimizer', 'nonce');

        global $wpdb;
        $test = $wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}page_assets_optimization_prefs'");
        if (!$test) {
            throw new Exception(esc_html__('Table does not exist', 'page-assets-optimizer'));
        }
        
        if (!isset($_POST['enabled'])) {
            throw new Exception(esc_html__('Missing optimization data', 'page-assets-optimizer'));
        }

        $enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 1;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries and cannot be cached
        $wpdb->query('START TRANSACTION');
        $result = $wpdb->replace(
            $wpdb->prefix.'page_assets_optimization_prefs',
            [
                'image_optimization' => $enabled,
                'updated_at' => gmdate('Y-m-d H:i:s')
            ]
        );
        
        if ($result === false) {
            /* translators: %s: Database error message */
            throw new Exception(sprintf(esc_html__('Database error: %s', 'page-assets-optimizer'), esc_html($wpdb->last_error)));
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries
        $wpdb->query('COMMIT');
        
        update_option('page_assets_image_optimization', $enabled);
        
        wp_send_json_success(['message' => 'Preferences saved successfully']);
    }
    
    public function saveMinify() {        
        if (!current_user_can('administrator')) {
            wp_send_json_error(__('Unauthorized', 'page-assets-optimizer'));
            return;
        }
        log_error('administrator saveMinify ' . current_user_can('administrator'));
        check_ajax_referer('page_assets_optimizer', 'nonce');
        
        global $wpdb;
        $test = $wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}page_assets_optimization_prefs'");
        if (!$test) {
            throw new Exception(esc_html__('Table does not exist', 'page-assets-optimizer'));
        }
        log_error('Post data: ' . print_r($_POST, true));
        
        if (!isset($_POST['enabledCss']) || !isset($_POST['enabledJs'])) {
            throw new Exception(esc_html__('Missing optimization data', 'page-assets-optimizer'));
        }

        $enabledCss = isset($_POST['enabledCss']) ? (int)$_POST['enabledCss'] : 1;
        $enabledJs = isset($_POST['enabledJs']) ? (int)$_POST['enabledJs'] : 1;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries and cannot be cached
        $wpdb->query('START TRANSACTION');
        $result = $wpdb->replace(
            $wpdb->prefix.'page_assets_optimization_prefs',
            [
                'minify_css' => $enabledCss,
                'minify_js' => $enabledJs,
                'updated_at' => gmdate('Y-m-d H:i:s')
            ]
        );
        log_error('Database error: ' . $wpdb->last_error);
        
        if ($result === false) {
            /* translators: %s: Database error message */
            throw new Exception(sprintf(esc_html__('Database error: %s', 'page-assets-optimizer'), esc_html($wpdb->last_error)));
        }
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Transactions require direct queries
        $wpdb->query('COMMIT');
        
        update_option('page_assets_minify_css', $enabledCss);
        update_option('page_assets_minify_js', $enabledJs);
        
        wp_send_json_success();
    }

    public function getSegmentList() {
        global $wpdb;

        $cache_key   = 'page_assets_segment_list';
        $cache_group = 'page_assets_optimizer';

        $allSegments = wp_cache_get($cache_key, $cache_group);

        if (false === $allSegments) {

            $results = $wpdb->get_results(
                "SELECT page_id FROM {$wpdb->prefix}page_assets_selections"
            );

            if ($results === null) {
                throw new Exception(esc_html__('Table does not exist', 'page-assets-optimizer'));
            }

            $allSegments = [];

            if (!empty($results)) {
                foreach ($results as $row) {
                    $pageID = trim($row->page_id);

                    // keep only non-numeric strings
                    if (!is_numeric($pageID) && is_string($pageID)) {
                        $allSegments[] = $pageID;
                    }
                }
            }

            wp_cache_set($cache_key, $allSegments, $cache_group);
        }

        wp_send_json_success($allSegments);
    }

    public function getSettingsPreferences() {
        global $wpdb;

        $cache_key   = 'page_assets_settings_preferences';
        $cache_group = 'page_assets_optimizer';

        $data = wp_cache_get($cache_key, $cache_group);

        if (false === $data) {

            // Get preferences (multiple rows)
            $preferences = $wpdb->get_results(
                "SELECT image_optimization, minify_css, minify_js 
                FROM {$wpdb->prefix}page_assets_optimization_prefs"
            );

            if ($preferences === null) {
                throw new Exception(esc_html__('Table does not exist', 'page-assets-optimizer'));
            }

            // Get count (single value)
            $pageCount = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}page_assets_selections"
            );

            if ($pageCount === null) {
                throw new Exception(esc_html__('Table does not exist', 'page-assets-optimizer'));
            }

            // Structure data properly (DON'T merge raw arrays)
            $data = [
                'preferences' => $preferences,
                'count'       => (int) $pageCount,
            ];

            wp_cache_set($cache_key, $data, $cache_group);
        }

        return $data;
    }

    public function enqueue_assets() {
        // enqueue scripts/styles here
    }
}