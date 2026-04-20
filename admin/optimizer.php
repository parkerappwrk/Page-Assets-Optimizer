<?php
if (!defined('ABSPATH')) exit;

class page_assets_optimizer_Optimizer {
    public function display() {
        wp_enqueue_style('page-assets-optimizer-admin', PAGE_ASSETS_OPTIMIZER_BASE_URL . '/assets/css/admin.css');
        wp_enqueue_script('page-assets-optimizer-admin', PAGE_ASSETS_OPTIMIZER_BASE_URL . '/assets/js/admin.js', array('jquery'), '1.0', true);
        
        wp_localize_script('page-assets-optimizer-admin', 'pageAssetsOptimizer', array(
            'nonce' => wp_create_nonce('page_assets_optimizer'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
        
        $pages = $this->getPublicPages();
        
        echo '<div class="main-page-assets-optimizer d-flex w-100 flex-column position-relative z-index-1">';
            echo '<div class="wrap page-assets-optimizer">';
            echo '<h1 class="page-title h1">'.esc_html__('Page Assets Optimizer', 'page-assets-optimizer').'</h1>';
            
            echo '<div class="optimizer-content flex-grow-1 w-100 d-flex flex-column mt-4">';
                $this->renderTabs();
                $this->renderPageSelector($pages);
                $this->renderOptimizerInterface();
            echo '</div>';

            echo '</div>';
        echo '</div>';
    }
    
    protected function getPublicPages() {
        return get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
    }

    protected function renderPageSelector($pages) {
        echo '<div class="page-selector mt-4" id="page-selector-box">';
            // Radio buttons
            echo '<div class="mb-3">';
                echo '<label class="form-label">'.esc_html__('Choose Input Method', 'page-assets-optimizer').'</label><br>';
                echo '<div class="form-check form-check-inline d-flex align-items-center gap-2 p-0">';
                    echo '<input class="form-check-input m-0" type="radio" name="page_input_mode" id="select_page_mode" value="select_page" checked>';
                    echo '<label class="form-check-label m-0" for="select_page_mode">'.esc_html__('Select a page', 'page-assets-optimizer').'</label>';
                echo '</div>';
                echo '<div class="form-check form-check-inline d-flex align-items-center gap-2 p-0">';
                    echo '<input class="form-check-input m-0" type="radio" name="page_input_mode" id="custom_url_mode" value="custom_url">';
                    echo '<label class="form-check-label m-0" for="custom_url_mode">'.esc_html__('Enter custom URL segment', 'page-assets-optimizer').'</label>';
                echo '</div>';
            echo '</div>';
        
            // Page dropdown
            echo '<div id="page-selector-dropdown">';
                echo '<label for="page-assets-selector" class="form-label">'.esc_html__('Select a page', 'page-assets-optimizer').'</label>';
                echo '<select id="page-assets-selector" class="form-select">';
                    echo '<option value="">'.esc_html__('Select a page', 'page-assets-optimizer').'</option>';
                    foreach ($pages as $page) {
                        echo '<option value="'.esc_attr($page->ID).'">'.esc_html($page->post_title).'</option>';
                    }
                echo '</select>';
            echo '</div>';
        
            // Custom URL segment input
            echo '<div id="custom-url-input" class="col-12 align-items-center gap-3" style="display: none;">
                <div class="col-5 position-relative">
                    <div class="campaign_name position-sticky top-0 p-2">
                        <label for="custom-url" class="form-label d-flex align-items-center">'.esc_html__('Enter URL segment', 'page-assets-optimizer').'
                            <span class="ms-2" data-bs-toggle="tooltip" title="Example: If your URL is https://example.com/events/my-events, then enter events or my-events">🛈</span>
                        </label>
                        <input type="text" id="custom-url" class="form-control" placeholder="e.g., events or my-events" autocomplete="off" aria-autocomplete="list" aria-controls="all-segment-list" autocorrect="off" autocapitalize="off" spellcheck="false"/>
                    </div>
                    <div class="auto-suggest-segment-list d-none flex-column position-absolute start-0 end-0 z-2" id="suggest-segment-list">
                            <div class="auto-suggest-list d-inline-flex col-12 flex-column overflow-y-auto" id="all-segment-list">
                                <h6 class="bg-dark-subtle p-3 mb-0">Added Segments</h6>
                                <ul class="list-unstyled mb-0" id="segment-list"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
    
        echo '</div>';
    }
    
    protected function renderTabs() {
        echo '<nav class="nav-tab-wrapper">
            <a href="#file-assets" data-tab="file-assets" class="nav-tab nav-tab-active">'.esc_html__('File Assets', 'page-assets-optimizer').'</a>
            <a href="#plugin" data-tab="plugin" class="nav-tab">'.esc_html__('Plugin', 'page-assets-optimizer').'</a>
            <a href="#images" data-tab="images" class="nav-tab">'.esc_html__('Images Optimization', 'page-assets-optimizer').'</a>
            <a href="#minify" data-tab="minify" class="nav-tab">'.esc_html__('Minify Files', 'page-assets-optimizer').'</a>
            <div class="indicator"></div>
        </nav>';
    }
    
    protected function renderOptimizerInterface() {
        echo '<div id="page-assets-optimizer-app">';
            echo '<div id="file-assets" class="optimizer-section">';
                echo '<h3>'.esc_html__('Optimize Page Assets', 'page-assets-optimizer').'</h3>';
                echo '<div class="asset-controls-css">';
                    echo '<div class="asset-controls">
                        <div class="all-assets-list-container" id="asset-list-css-container"></div>
                    </div>';
                    echo '<div class="additional-files-section mt-4">
                        <h5>'.esc_html__('Additional CSS Files to Exclude', 'page-assets-optimizer').'</h5>
                        <div class="additional-files-container">
                            <div class="file-input-group mb-2">
                                <input type="text" class="form-control additional-file-input" placeholder="'.esc_html__('Enter file name', 'page-assets-optimizer').'">
                                <button class="btn btn-outline-primary add-file-css-btn ms-2 add-file-btn" data-type="css">'.esc_html__('Add', 'page-assets-optimizer').'</button>
                            </div>
                            <div id="additional-css-files-list"></div>
                        </div>
                    </div>';
                    echo '<div class="all-assets-button-group d-none">';
                        echo '<div class="button-group">
                            <button class="btn btn-outline-primary select-all" data-type="css">'.esc_html__('Select All CSS', 'page-assets-optimizer').'</button>
                            <button class="btn btn-outline-primary deselect-all" data-type="css">'.esc_html__('Deselect All CSS', 'page-assets-optimizer').'</button>
                        </div>';
                    echo '</div>';
                echo '</div>';

                echo '<div class="asset-controls-js">';
                    echo '<div class="asset-controls">
                        <div class="all-assets-list-container" id="asset-list-js-container"></div>
                    </div>';
                    echo '<div class="additional-files-section mt-4">
                        <h5>'.esc_html__('Additional JS Files to Exclude', 'page-assets-optimizer').'</h5>
                        <div class="additional-files-container">
                            <div class="file-input-group mb-2">
                                <input type="text" class="form-control additional-file-input" placeholder="'.esc_html__('Enter file name', 'page-assets-optimizer').'">
                                <button class="btn btn-outline-primary add-file-js-btn ms-2 add-file-btn" data-type="js">'.esc_html__('Add', 'page-assets-optimizer').'</button>
                            </div>
                            <div id="additional-js-files-list"></div>
                        </div>
                    </div>';
                    echo '<div class="all-assets-button-group d-none">
                        <div class="button-group">
                            <button class="btn btn-outline-primary select-all" data-type="js">'.esc_html__('Select All JS', 'page-assets-optimizer').'</button>
                            <button class="btn btn-outline-primary deselect-all" data-type="js">'.esc_html__('Deselect All JS', 'page-assets-optimizer').'</button>
                        </div>
                        <div class="button-group mt-4 mb-2">
                            <button class="btn btn-success save-selection px-4">'.esc_html__('Save Selection', 'page-assets-optimizer').'</button>
                        </div>
                    </div>';
                echo '</div>';
            echo '</div>';
            $this->renderPluginOptimizer();
            $this->renderImageOptimizer();
            $this->renderMinifyOptimizer();

            echo '<div class="disclaimer-note w-100 mt-4">
                <p class="fst-italic">'.esc_html__('Note: Selected files will be offloaded from the page you have selected.', 'page-assets-optimizer').'</p>
            </div>';
        echo '</div>';
    }
    
    protected function renderPluginOptimizer() {
        if (current_user_can('administrator')) {
            // Plugin list with removal options
            $active_plugins = get_option('active_plugins');
            echo '<div class="plugin-optimizer w-100" id="plugin" style="display: none;">
                <h3>'.esc_html__('Plugin Optimization', 'page-assets-optimizer').'</h3>
                <div class="plugin-list-container w-100">
                    <div class="plugin-list" id="plugin-list">
                        <h5>'.esc_html__('Select Plugins to Remove', 'page-assets-optimizer').'</h5>
                        <ul>';
                        $pluginCount = 0;
                            foreach ($active_plugins as $plugin_path) {
                                $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
                                $plugin_name = $plugin_data['Name'] ?? basename($plugin_path);
                                
                                echo '<li class="asset-list-row" data-src="' . esc_attr($plugin_path) . '">
                                        <input type="checkbox" name="plugin_to_remove[]" value="' . esc_attr($plugin_path) . '" id="plugin_' . esc_attr($pluginCount) . '_remove">
                                        <label for="plugin_' . esc_attr($pluginCount) . '_remove">' . esc_html($plugin_name) . '</label>
                                </li>';
                                $pluginCount++;
                            }
                        echo '</ul>
                    </div>
                </div>';
            
            // Action buttons
            echo '<div class="button-group">
                <button class="btn btn-outline-primary select-all" data-type="plugin">'.esc_html__('Select All Plugins', 'page-assets-optimizer').'</button>
                <button class="btn btn-outline-primary deselect-all" data-type="plugin">'.esc_html__('Deselect All Plugins', 'page-assets-optimizer').'</button>
            </div>
            <div class="d-flex justify-content-start">
                <button class="btn btn-success optimize-plugins save-plugin-selection">'.esc_html__('Save Plugin Preferences', 'page-assets-optimizer').'</button>
            </div>
        </div>';
        } else {
            echo '<p>'.esc_html__('You need administrator privileges to manage plugins.', 'page-assets-optimizer').'</p>';
        }
        echo '</div>';
    }

    protected function renderImageOptimizer() {
        if (current_user_can('administrator')) {
            echo '<div class="optimizer-section w-100" id="images" style="display: none;">';
                echo '<h3>'.esc_html__('Image Optimization', 'page-assets-optimizer').'</h3>';
                echo '<div class="plugin-list-container w-100">';
                    echo '<div class="alert alert-info mb-4 mt-4 w-100">';
                        echo '<h5><strong>'.esc_html__('Automatic Image Optimization', 'page-assets-optimizer').'</strong></h5>';
                        echo '<p>'.esc_html__('Our system automatically converts your images to modern formats (WebP/AVIF) when needed, based on browser support.', 'page-assets-optimizer').'</p>';
                        echo '<ul>';
                            echo '<li>'.esc_html__('No manual conversion required - we handle it automatically', 'page-assets-optimizer').'</li>';
                            echo '<li>'.esc_html__('Faster page loads with smaller image files', 'page-assets-optimizer').'</li>';
                            echo '<li>'.esc_html__('Maintains original quality while reducing file size', 'page-assets-optimizer').'</li>';
                            echo '<li>'.esc_html__('Works for both existing and new images', 'page-assets-optimizer').'</li>';
                        echo '</ul>';
                    echo '</div>';
                    
                    echo '<div class="form-check form-switch mb-3 d-flex align-items-center gap-2 ps-0">';
                        echo '<input type="checkbox" class="ms-0" id="image-optimization-toggle">';
                        echo '<label for="image-optimization-toggle"><strong>'.esc_html__('Enable automatic image optimization', 'page-assets-optimizer').'</strong></label>';
                    echo '</div>';
                    echo '<div class="button-group mt-4 mb-2">';
                        echo '<button class="btn btn-success optimize-images save-image-selection">'.esc_html__('Save Image Preferences', 'page-assets-optimizer').'</button>';
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        } else {
            echo '<p>'.esc_html__('You need administrator privileges to manage image optimization.', 'page-assets-optimizer').'</p>';
        }
    }

    protected function renderMinifyOptimizer() {
        if (current_user_can('administrator')) {            
            echo '<div class="optimizer-section w-100" id="minify" style="display: none;">';
                echo '<h3>'.esc_html__('Minify Optimization', 'page-assets-optimizer').'</h3>';
                echo '<div class="plugin-list-container w-100">';
                    echo '<div class="alert alert-info mb-4 mt-4 w-100">';
                        echo '<h5><strong>'.esc_html__('Automatic File Minification', 'page-assets-optimizer').'</strong></h5>';
                        echo '<p>'.esc_html__('Our system automatically minifies your CSS and JavaScript files for optimal performance.', 'page-assets-optimizer').'</p>';
                        echo '<ul>';
                            echo '<li>'.esc_html__('No manual minification required - we handle it automatically', 'page-assets-optimizer').'</li>';
                            echo '<li>'.esc_html__('Reduces file sizes by removing unnecessary characters', 'page-assets-optimizer').'</li>';
                            echo '<li>'.esc_html__('Maintains full functionality while improving load times', 'page-assets-optimizer').'</li>';
                            echo '<li>'.esc_html__('Works for both theme and plugin assets', 'page-assets-optimizer').'</li>';
                        echo '</ul>';
                    echo '</div>';
                    
                    echo '<div class="form-check form-switch mb-3 d-flex align-items-center gap-2 ps-0">';
                        echo '<input type="checkbox" class="ms-0" id="minify-css-toggle" />';
                        echo '<label for="minify-css-toggle"><strong>'.esc_html__('Enable CSS minification', 'page-assets-optimizer').'</strong></label>';
                    echo '</div>';
                    
                    echo '<div class="form-check form-switch mb-3 d-flex align-items-center gap-2 ps-0">';
                        echo '<input type="checkbox" class="ms-0" id="minify-js-toggle" />';
                        echo '<label for="minify-js-toggle"><strong>'.esc_html__('Enable JavaScript minification', 'page-assets-optimizer').'</strong></label>';
                    echo '</div>';
                    
                echo '<div class="button-group mt-4 mb-2">';
                    echo '<button class="btn btn-success optimize-minify save-minify-selection">'.esc_html__('Save Minification Preferences', 'page-assets-optimizer').'</button>';
                echo '</div>';
                echo '</div>';
            echo '</div>';
        } else {
            echo '<p>'.esc_html__('You need administrator privileges to manage plugins.', 'page-assets-optimizer').'</p>';
        }
        echo '</div>';
    }
}
