<?php
if (!defined('ABSPATH')) exit;

class page_assets_optimizer_Dashboard {
    public $settingsPreferences;

    public function __construct($settingsPreferences) {
        $this->settingsPreferences = $settingsPreferences;
    }

    public function display() {
        wp_enqueue_style('page-assets-optimizer-admin', PAGE_ASSETS_OPTIMIZER_BASE_URL . '/assets/css/admin.css');
        wp_enqueue_script('page-assets-optimizer-admin', PAGE_ASSETS_OPTIMIZER_BASE_URL . '/assets/js/admin.js', array('jquery'), '1.0', true);
        
        wp_localize_script('page-assets-optimizer-admin', 'pageAssetsOptimizer', array(
            'nonce' => wp_create_nonce('page_assets_optimizer'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));

        echo '<div class="wrap">
            <div class="page-assets-optimizer-header mb-5">
                <h1 class="mb-3 h1">'.esc_html__('Page Assets Optimizer', 'page-assets-optimizer').'</h1>
                <p class="mb-0 fs-6">'.esc_html__('Optimize your website performance by managing assets and images efficiently', 'page-assets-optimizer').'</p>
            </div>';

        //Dashboard Stats Box
        echo '<div class="stats-section mb-5">
            <h3 class="mb-4">'.esc_html__('Optimization Status', 'page-assets-optimizer').'</h3>
            <div class="col-12 d-flex gap-4">';
                // Image Optimization Section
                echo '<div class="col-md-3 p-0 mb-4 flex-shrink-1">
                    <div class="card stats-card shadow-sm p-0 h-100">
                        <div class="card-body p-0 d-flex flex-column">
                            <div class="d-flex align-items-start flex-column gap-2 mb-3 py-3 px-4">
                                <div class="card-logo flex-shrink-0 me-3">
                                    '.wp_get_attachment_image(
                                        $this->get_image_attachment_id('image-optimization.png'),
                                        array(50, 50),
                                        false,
                                        array(
                                            'alt' => esc_attr__('Image Optimization', 'page-assets-optimizer'),
                                            'title' => esc_attr__('Image Optimization', 'page-assets-optimizer')
                                        )
                                    ).'
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="company-name">'.esc_html__('Image Optimization', 'page-assets-optimizer').'</h5>
                                    <div class="company-description">
                                        '.esc_html__('Optimize your website performance by managing assets and images efficiently.', 'page-assets-optimizer').'
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-end optimization-status p-3 mt-auto '.($this->settingsPreferences[0]->image_optimization == 1 ? 'active' : '').'" id="image-optimization-status">
                                <label class="toggle-switch">
                                    <svg width="40" height="40" x="0" y="0" viewBox="0 0 512 512"><g fill-rule="evenodd" clip-rule="evenodd"><path fill="#4bae4f" d="M256 0C114.8 0 0 114.8 0 256s114.8 256 256 256 256-114.8 256-256S397.2 0 256 0z" opacity="1"></path><path fill="#ffffff" d="M379.8 169.7c6.2 6.2 6.2 16.4 0 22.6l-150 150c-3.1 3.1-7.2 4.7-11.3 4.7s-8.2-1.6-11.3-4.7l-75-75c-6.2-6.2-6.2-16.4 0-22.6s16.4-6.2 22.6 0l63.7 63.7 138.7-138.7c6.2-6.3 16.4-6.3 22.6 0z" opacity="1"></path></g></g></svg>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>';
                // CSS Minify Section
                echo '<div class="col-md-3 p-0 mb-4 flex-shrink-1">
                    <div class="card stats-card shadow-sm p-0 h-100">
                        <div class="card-body p-0 d-flex flex-column">
                            <div class="d-flex align-items-start flex-column gap-2 mb-3 py-3 px-4">
                                <div class="card-logo flex-shrink-0 me-3">
                                    '.wp_get_attachment_image(
                                        $this->get_image_attachment_id('css-minification.svg'),
                                        array(50, 50),
                                        false,
                                        array(
                                            'alt' => esc_attr__('CSS Minification', 'page-assets-optimizer'),
                                            'title' => esc_attr__('CSS Minification', 'page-assets-optimizer')
                                        )
                                    ).'
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="company-name">'.esc_html__('CSS Minification', 'page-assets-optimizer').'</h5>
                                    <div class="company-description">
                                        '.esc_html__('Optimize your website performance by minifying CSS files.', 'page-assets-optimizer').'
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-end optimization-status p-3 mt-auto '.($this->settingsPreferences[0]->minify_css == 1 ? 'active' : '').'" id="css-minification-status">
                                <label class="toggle-switch">
                                    <svg width="40" height="40" x="0" y="0" viewBox="0 0 512 512"><g fill-rule="evenodd" clip-rule="evenodd"><path fill="#4bae4f" d="M256 0C114.8 0 0 114.8 0 256s114.8 256 256 256 256-114.8 256-256S397.2 0 256 0z" opacity="1"></path><path fill="#ffffff" d="M379.8 169.7c6.2 6.2 6.2 16.4 0 22.6l-150 150c-3.1 3.1-7.2 4.7-11.3 4.7s-8.2-1.6-11.3-4.7l-75-75c-6.2-6.2-6.2-16.4 0-22.6s16.4-6.2 22.6 0l63.7 63.7 138.7-138.7c6.2-6.3 16.4-6.3 22.6 0z" opacity="1"></path></g></g></svg>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>';
                // JS Minify Section
                echo '<div class="col-md-3 p-0 mb-4 flex-shrink-1">
                    <div class="card stats-card shadow-sm p-0 h-100">
                        <div class="card-body p-0 d-flex flex-column">
                            <div class="d-flex align-items-start flex-column gap-2 mb-3 py-3 px-4">
                                <div class="card-logo flex-shrink-0 me-3">
                                    '.wp_get_attachment_image(
                                        $this->get_image_attachment_id('js-minification.svg'),
                                        array(50, 50),
                                        false,
                                        array(
                                            'alt' => esc_attr__('JS Minification', 'page-assets-optimizer'),
                                            'title' => esc_attr__('JS Minification', 'page-assets-optimizer')
                                        )
                                    ).'
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="company-name">'.esc_html__('JS Minification', 'page-assets-optimizer').'</h5>
                                    <div class="company-description">
                                        '.esc_html__('Optimize your website performance by minifying JS files.', 'page-assets-optimizer').'
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-end optimization-status p-3 mt-auto '.($this->settingsPreferences[0]->minify_js == 1 ? 'active' : '').'" id="js-minification-status">
                                <label class="toggle-switch">
                                    <svg width="40" height="40" x="0" y="0" viewBox="0 0 512 512"><g fill-rule="evenodd" clip-rule="evenodd"><path fill="#4bae4f" d="M256 0C114.8 0 0 114.8 0 256s114.8 256 256 256 256-114.8 256-256S397.2 0 256 0z" opacity="1"></path><path fill="#ffffff" d="M379.8 169.7c6.2 6.2 6.2 16.4 0 22.6l-150 150c-3.1 3.1-7.2 4.7-11.3 4.7s-8.2-1.6-11.3-4.7l-75-75c-6.2-6.2-6.2-16.4 0-22.6s16.4-6.2 22.6 0l63.7 63.7 138.7-138.7c6.2-6.3 16.4-6.3 22.6 0z" opacity="1"></path></g></g></svg>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>';
                // Page Assets Optimizer Section
                echo '<div class="col-md-3 p-0 mb-4 flex-shrink-1">
                    <div class="card stats-card shadow-sm p-0 h-100">
                        <div class="card-body p-0 d-flex flex-column">
                            <div class="d-flex align-items-start flex-column gap-2 mb-3 py-3 px-4">
                                <div class="card-logo flex-shrink-0 me-3">
                                    '.wp_get_attachment_image(
                                        $this->get_image_attachment_id('speed-radar.png'),
                                        array(50, 50),
                                        false,
                                        array(
                                            'alt' => esc_attr__('Page Assets Optimizer', 'page-assets-optimizer'),
                                            'title' => esc_attr__('Page Assets Optimizer', 'page-assets-optimizer')
                                        )
                                    ).'
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="company-name">'.esc_html__('Page Assets Optimizer', 'page-assets-optimizer').'</h5>
                                    <div class="company-description">
                                        '.esc_html__('Optimize your page assets performance by managing and restricting assets like JS and CSS files.', 'page-assets-optimizer').'
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-end optimization-status p-3 mt-auto '.($this->settingsPreferences[1]->count > 0 ? 'active' : '').'" id="page-assets-optimizer-status">
                                <label class="toggle-switch">
                                    <svg width="40" height="40" x="0" y="0" viewBox="0 0 512 512"><g fill-rule="evenodd" clip-rule="evenodd"><path fill="#4bae4f" d="M256 0C114.8 0 0 114.8 0 256s114.8 256 256 256 256-114.8 256-256S397.2 0 256 0z" opacity="1"></path><path fill="#ffffff" d="M379.8 169.7c6.2 6.2 6.2 16.4 0 22.6l-150 150c-3.1 3.1-7.2 4.7-11.3 4.7s-8.2-1.6-11.3-4.7l-75-75c-6.2-6.2-6.2-16.4 0-22.6s16.4-6.2 22.6 0l63.7 63.7 138.7-138.7c6.2-6.3 16.4-6.3 22.6 0z" opacity="1"></path></g></g></svg>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>';
            echo '</div>';
        echo '</div>';
        
        // Features section
        echo '<div class="features-section mb-5">
            <div class="col-12 d-flex gap-4">';
        
        // Feature 1: Asset Management
        echo '<div class="col-md-6 p-0 mb-4 flex-shrink-1">
            <h3 class="mb-4">'.esc_html__('Key Features', 'page-assets-optimizer').'</h3>
            <div class="col-12 d-flex flex-column border rounded-3 p-0">
                <div class="feature-card p-5 border-1 border-bottom w-100">
                    <div class="card-body">
                        <div class="feature-icon mb-3 text-primary d-flex align-items-center gap-2">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <h3 class="h4 text-black mb-0">'.esc_html__('Smart Asset Management', 'page-assets-optimizer').'</h3>
                        </div>
                        <ul class="feature-list">
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Selectively load assets only on pages where they are needed', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Remove unused CSS and JavaScript to reduce bloat', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Optimize plugin asset loading for better performance', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Conditional loading of WooCommerce assets on product pages only', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Disable emoji scripts and other unnecessary WordPress defaults', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Smart detection of render-blocking resources', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Visual interface to manage asset loading rules', 'page-assets-optimizer').'</li>
                        </ul>
                    </div>
                </div>
                
                <div class="feature-card p-5 border-1 border-bottom w-100">
                    <div class="card-body">
                        <div class="feature-icon mb-3 text-success d-flex align-items-center gap-2">
                            <span class="dashicons dashicons-format-image"></span>
                            <h3 class="h4 text-black mb-0">'.esc_html__('Automatic Image Optimization', 'page-assets-optimizer').'</h3>
                        </div>
                        <ul class="feature-list">
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('On-the-fly JPG/JPEG compression with adjustable quality', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('PNG optimization without visible quality loss', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Automatic WebP conversion for supported browsers', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Lazy loading for all images to improve initial page load', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Responsive image srcset generation', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Automatic retina/HiDPI image support', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Image dimension attributes for better CLS scores', 'page-assets-optimizer').'</li>
                        </ul>
                    </div>
                </div>
                
                <div class="feature-card p-5 border-1 border-bottom w-100">
                    <div class="card-body">
                        <div class="feature-icon mb-3 text-warning d-flex align-items-center gap-2">
                            <span class="dashicons dashicons-editor-code"></span>
                            <h3 class="h4 text-black mb-0">'.esc_html__('Code Minification', 'page-assets-optimizer').'</h3>
                        </div>
                        <ul class="feature-list">
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('CSS minification to reduce file sizes by 30-60%', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('JavaScript minification for faster parsing and execution', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Safe minification that preserves all functionality', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('HTML minification to reduce page weight', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Comment and whitespace removal from all assets', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Option to exclude specific files from minification', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Automatic cache busting when files change', 'page-assets-optimizer').'</li>
                        </ul>
                    </div>
                </div>
            
                <div class="feature-card p-5  w-100">
                    <div class="card-body">
                        <div class="feature-icon mb-3 text-info d-flex align-items-center gap-2">
                            <span class="dashicons dashicons-performance"></span>
                            <h3 class="h4 text-black mb-0">'.esc_html__('Performance Benefits', 'page-assets-optimizer').'</h3>
                        </div>
                        <ul class="feature-list">
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Faster page load times (typically 30-50% improvement)', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Reduced server bandwidth usage by up to 70%', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Improved Core Web Vitals scores for better SEO', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Better user experience with faster interactivity', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Reduced hosting costs through lower resource usage', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Improved conversion rates from faster page speeds', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-yes"></span>'.esc_html__('Mobile performance optimization for all devices', 'page-assets-optimizer').'</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>';
        
        // Getting started section
        echo '<div class="col-md-6 p-0 mb-4 flex-shrink-1">
            <div class="getting-started-section col-12">
                <h3 class="mb-4">'.esc_html__('Getting Started', 'page-assets-optimizer').'</h3>
                <div class="col-12 p-5 border rounded-3">
                    <div class="card-body">
                        <div class="feature-icon mb-3 text-info d-flex align-items-center gap-2">
                            <span class="dashicons dashicons-performance"></span>
                            <h3 class="h4 text-black mb-0">'.esc_html__('Key steps to get started', 'page-assets-optimizer').'</h3>
                        </div>
                        <ol class="m-0">
                            <li class="mb-3 d-flex align-items-start gap-2"><span class="dashicons dashicons-arrow-right"></span>'.esc_html__('Navigate to the <b>Optimizer tab</b> to configure settings', 'page-assets-optimizer').'</li>
                            <li class="mb-3 d-flex align-items-start gap-2"><span class="dashicons dashicons-arrow-right"></span>'.esc_html__('Select <b>pages</b> you want to optimize.', 'page-assets-optimizer').'</li>
                            <li class="mb-3 d-flex align-items-start gap-2"><span class="dashicons dashicons-arrow-right"></span>'.esc_html__('Choose <b>optimization preferences</b>', 'page-assets-optimizer').'</li>
                            <li class="d-flex align-items-start gap-2"><span class="dashicons dashicons-arrow-right"></span>'.esc_html__('Click <b>Save changes</b> and enjoy <b>faster page loads</b>!', 'page-assets-optimizer').'</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>';
        
        echo '</div>'; // Close features section
        echo '</div>'; // Close wrap
    }
}
