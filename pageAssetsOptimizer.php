<?php
/**
 * Plugin Name: Page Assets Optimizer
 * Description: <strong>Supercharge your page speed</strong> with advanced asset management. Selectively disable CSS/JS files or entire plugins on specific pages. Key features: <strong>Granular control</strong>: Disable individual assets or full plugins, <strong>Automatic image optimization</strong>: Convert JPG/PNG to WebP/AVIF on-the-fly, <strong>File minification</strong>: Automatically minify CSS and JavaScript, <strong>Performance tracking</strong>: Monitor speed improvements
 * Author: Parker
 * Author URI: https://profiles.wordpress.org/parkers
 * Version: 1.0.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$pageAssetsOptimizerUrl = plugin_dir_url(__FILE__);
$pageAssetsOptimizerPath = plugin_dir_path(__FILE__);
if(substr($pageAssetsOptimizerUrl, -1) == "/" || substr($pageAssetsOptimizerUrl, -1) == "\\" ){
	$pageAssetsOptimizerUrl  = substr($pageAssetsOptimizerUrl, 0, strlen($pageAssetsOptimizerUrl)-1 );
}
if(substr($pageAssetsOptimizerPath, -1) == "/" || substr($pageAssetsOptimizerPath, -1) == "\\" ){
	$pageAssetsOptimizerPath  = substr($pageAssetsOptimizerPath, 0, strlen($pageAssetsOptimizerPath)-1 );
}

define("PAGE_ASSETS_OPTIMIZER_BASE_URL", $pageAssetsOptimizerUrl);
define("PAGE_ASSETS_OPTIMIZER_ADMIN_URL", get_admin_url().'admin.php?page=pageAssetsOptimizer');
define("PAGE_ASSETS_OPTIMIZER_BASE_PATH", $pageAssetsOptimizerPath);
define("PAGE_ASSETS_OPTIMIZER_PLUGIN_VERSION", "1.0");
define("PAGE_ASSETS_OPTIMIZER_SERVER_SUB_FOLDER", '');
define("PAGE_ASSETS_OPTIMIZER_TIMEOUT_SECONDS", 30);
define("PAGE_ASSETS_OPTIMIZER_FILE", __FILE__);

global $pageAssetsOptimizerAPIClient;

// Load core files
include_once('library/core.php');
include_once('library/hooks.php');
include_once('library/shortcode.php');
include_once('library/plugin.php');
include_once('library/frontend-controller.php');

// Load admin files
if (is_admin()) {
    include_once('admin/dashboard.php');
    include_once('admin/settings.php');
    include_once('admin/optimizer.php');
}

// Register activation hook
register_activation_hook(__FILE__, array('page_assets_optimizer_plugin', 'ptpfrw_activate'));
register_activation_hook(__FILE__, array('pageAssetsOptimizer_hooks', 'page_assets_optimizer_create_mu_plugin'));

// Initialize plugin
$pageAssetsOptimizer_plugin = page_assets_optimizer_plugin::init();
