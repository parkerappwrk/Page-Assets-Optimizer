<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class pageAssetsOptimizer_core{
	
    public $pluginOptions;
    public $pluginSettings;
    public $logErrors;
    public $homeUrl;
	public $siteUrl;
	public $uploadDirPath;
	public $uploadDirUrl;
	public $uploadTempUrl;
    public $useErrorLog = false;
	public $keysExepmtedFromSanitize= array();
    public $upload_info;
    public $adminNonceString = "ptpfrw_admin_nonce";
    public $siteNonceString = "ptpfrw_site_nonce";
    public $tablePrefix = "page_assets_optimizer_";
	public $itemsPerPage;
	public $offset;
	public $ApiItemsPerPage;
	public $lqMenuCapabilities;
	public $specialFields=array();
	public $urlWithNewTyle;
    public $validImageTypes;
    public $maxImageSize;
    public $campaignencryptKey = '';
    public $tableLogs;
    public $tablePreferences;
    public $tableSelections;
    public $ptpfrwTtempCsvFile;
    public $packageImagePath;
    public $packageImageURL;
	
	// Declare properties to fix deprecation warnings
	// public $tableLogs;
	// public $tablePreferences;
	// public $tableSelections;
	// public $ptpfrwTtempCsvFile;
	// public $packageImagePath;
	// public $packageImageURL;
	
	function __construct()
    {
        // Initialize WP_Filesystem
        global $wp_filesystem;
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        
        $this->setTableNames();
        $this->pluginOptions                = array('data_type' => '_ptpfrw_data_type');
        $this->validImageTypes              = array("image/png", "image/jpg", "image/jpeg");
		$this->itemsPerPage                 = 15;
		$this->ApiItemsPerPage              = 5;
		$this->offset                       = 0;
        $this->maxImageSize                 = "1024000";    //1 MB
		$this->siteUrl                      = get_site_url();
        $this->homeUrl                      = network_home_url();
        $this->upload_info                  = wp_upload_dir();
        $this->uploadDirPath                = $this->upload_info['basedir'];
        $this->uploadDirUrl                 = $this->upload_info['baseurl'];
		$this->ptpfrwTtempCsvFile           = $this->uploadDirPath . "/tempCsvFile/";
        $this->uploadTempUrl                = $this->uploadDirUrl . "/tempCsvFile/";
        $this->packageImagePath             = $this->uploadDirPath . "/CampaignImages/";
        $this->packageImageURL              = $this->uploadDirUrl . "/CampaignImages/";
		$this->lqMenuCapabilities           = "activate_plugins";
        $this->campaignencryptKey          = 'APPWRK-OPTIMIZE-ASSETS';
	}
	
	public function setTableNames()
    {
        global $wpdb;
        $this->tableLogs                       = $wpdb->prefix ."page_assets_optimizer_logs";
        $this->tablePreferences                = $wpdb->prefix ."page_assets_preferences";
        $this->tableSelections                 = $wpdb->prefix ."page_assets_selections";
            
    }
	
	public function check_current_screen()
    {
        $screen = get_current_screen();
    }

	protected function sanitizeVariables($input)
    {
        $output = array();
        if (is_array($input) && count($input) > 0) {
            foreach ($input as $k => $v) {
                if (!in_array($k, $this->keysExepmtedFromSanitize)) {
                    $output[$k] = sanitize_text_field($v);
                } else {
                    $output[$k] = $v;
                }
            }
        }
        return $output;
    }

    public function log_error($error, $onlySelected = false)
    {
        if (
            ($this->useErrorLog === true || $onlySelected === true) &&
            defined('WP_DEBUG') && WP_DEBUG
        ) {
            $this->log(true);

            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only runs in debug mode.
            error_log(is_scalar($error) ? $error : wp_json_encode($error));
        }
    }

    public function log($logError = false)
    {
        global $wp_filesystem;
        $this->errorFileDir = esc_url(PAGE_ASSETS_OPTIMIZER_BASE_URL . '/logs');
        $this->errorFile = $this->errorFileDir . '/error.log';
        if (!$wp_filesystem->exists($this->errorFileDir)) {
            $wp_filesystem->mkdir($this->errorFileDir, FS_CHMOD_DIR);
        } else if (substr($wp_filesystem->getchmod($this->errorFileDir), 0, -3) != '777') {
            $wp_filesystem->chmod($this->errorFileDir, FS_CHMOD_DIR);
        }

        $this->logErrors = $logError;
        if ($this->logErrors) {
            $file = plugin_dir_path(__FILE__) . 'error.log';
			file_put_contents($file, wp_json_encode($error) . PHP_EOL, FILE_APPEND);
            if (!$wp_filesystem->exists($this->errorFile)) {
                $wp_filesystem->touch($this->errorFile);
                $wp_filesystem->chmod($this->errorFile, FS_CHMOD_FILE);
            }
        }
    }

    function getPluginSettings($param = '')
    {
        $setting = array();

        foreach ($this->pluginOptions as $k => $v) {
            $setting[$k] = get_option($v, NULL);
        }
        $this->pluginSettings = $setting;
        return $setting;
    }

    public function campaign_aes_encrypt($data, $key)
    {
        // AES encryption using ECB mode and PKCS7 padding
        $encrypted = openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        // Convert binary data to base64-encoded string
        return base64_encode($encrypted);
    }
    
    // AES decryption function
    public function campaign_aes_decrypt($data, $key) {
        // Convert base64-encoded string to binary data
        $data = base64_decode($data);
        // AES decryption using ECB mode and PKCS7 padding
        return openssl_decrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }
    
    protected function createTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->tablePreferences} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            page_id bigint(20) NOT NULL,
            preferences longtext NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY page_id (page_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
	
}