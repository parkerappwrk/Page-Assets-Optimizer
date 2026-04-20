<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class pageAssetsOptimizer_hooks extends pageAssetsOptimizer_core{
	
	public function __construct(){
		parent::__construct();
		$this->executeHooks();
	}
	
	public function executeHooks(){
		
	}

    public static function page_assets_optimizer_create_mu_plugin() {
        global $wp_filesystem;
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        
        $mu_plugin_dir = WPMU_PLUGIN_DIR;
        $mu_plugin_file = $mu_plugin_dir . '/page-assets-optimizer-mu.php';
        
        if (!$wp_filesystem->exists($mu_plugin_dir)) {
            $wp_filesystem->mkdir($mu_plugin_dir, FS_CHMOD_DIR);
        }
        
        if (!$wp_filesystem->is_writable($mu_plugin_dir)) {
            log_error('Page Assets Optimizer: MU plugins directory is not writable.');
            return;
        }
        
        $content = '<?php
        add_filter("option_active_plugins", function($plugins) {
            $prefs = maybe_unserialize(get_option("page_assets_optimizer_plugins_prefs"));
            $to_remove = $prefs["plugins_to_remove"] ?? [];

            foreach ($to_remove as $plugin) {
                $key = array_search($plugin, $plugins);
                if ($key !== false) {
                    unset($plugins[$key]);
                }
            }

            return $plugins;
        }, 999);
        ';

        if (!$wp_filesystem->put_contents($mu_plugin_file, $content, FS_CHMOD_FILE)) {
            log_error('Page Assets Optimizer: Failed to write MU plugin file.');
        }
    }

}
