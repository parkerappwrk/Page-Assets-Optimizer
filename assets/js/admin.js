jQuery(document).ready(function($) {
    const selectRadio = document.getElementById("select_page_mode");
    const customRadio = document.getElementById("custom_url_mode");
    const dropdown = document.getElementById("page-selector-dropdown");
    const customInput = document.getElementById("custom-url-input");
    const pageSelector = document.getElementById("page-assets-selector");

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    getSegmentList();

    getAssetsPreferences();
    
    function toggleInputMode() {
        if (selectRadio.checked) {
            dropdown.style.display = "block";
            customInput.style.display = "none";
        } else {
            dropdown.style.display = "none";
            customInput.style.display = "inline-flex";
            pageSelector.selectedIndex = 0;
            setTimeout(() => {
                fetchAssetsByUrl();
            }, 100);
        }
        $('#asset-list-css-container, #additional-css-files-list, #asset-list-js-container, #additional-js-files-list').html('');
        $('.all-assets-button-group').addClass('d-none');
    }

    selectRadio.addEventListener("change", toggleInputMode);
    customRadio.addEventListener("change", toggleInputMode);

    // Tab switching
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show corresponding section
        $('.optimizer-section').hide();
        $('.plugin-optimizer').hide();
        $('#'+$(this).attr('data-tab')).show();
        if($(this).attr('data-tab') === 'images' || $(this).attr('data-tab') === 'minify'){
            $('#page-selector-box').hide();
        }else{
            $('#page-selector-box').show();
        }
    });
    
    // Page selection handler
    
    $('#page-assets-selector').on('change', function(event) {
        event.preventDefault();
        const pageId = $(this).val();
        if (!pageId) return;
        
        $('.asset-list').html('<p>Loading assets...</p>');
        
        $.post(pageAssetsOptimizer.ajaxurl, {
            action: 'page_assets_get_assets',
            page_id: pageId,
            nonce: pageAssetsOptimizer.nonce
        }, function(response) {
            if (response.success) {
                updateAssetList(response.data);
                $('.all-assets-button-group').removeClass('d-none');
                loadAssetsWithPreferences(pageId);
            } else {
                $('.asset-list').html('<p class="error">Error loading assets</p>');
            }
        });
    });
    
    // Load assets and apply preferences when page is selected
    function loadAssetsWithPreferences(pageId) {        
        Promise.all([
            $.get(pageAssetsOptimizer.ajaxurl, {
                action: 'page_assets_get_preferences',
                page_id: pageId, 
                nonce: pageAssetsOptimizer.nonce
            })
        ]).then(function(responses) {
            renderAssetsWithPreferences(responses[0].data);
        }).catch(function(error) {
            console.log(error);
        });
    }
    
    //Render assets with preferences
    function renderAssetsWithPreferences(assets) {
        let assetsCSS = $('#asset-list-css-container ul li'),
        assetsJS = $('#asset-list-js-container ul li'),
        assetsPlugin = $('#plugin-list ul li');

        let collectAllRegexCSS = assets.styles_to_remove_by_regex,
        collectAllRegexJS = assets.scripts_to_remove_by_regex;
        
       
        //Render CSS assets with preferences
        if (assetsCSS.length) {
            assetsCSS.each(function() {
                const handleMatch = assets.styles_to_remove?.includes($(this).find('.asset-handle').text());
                const regexMatch = assets.styles_to_remove_by_regex?.some(regexString => {
                    try {
                        const cleanedPattern = regexString.replace(/\\\\/g, '\\');
                        const pattern = new RegExp(cleanedPattern);
                        return pattern.test($(this).find('input').val());
                    } catch (e) {
                        showToast('Invalid regex: ' + regexString + ' - ' + e, 'error');
                        return false;
                    }
                });

                for (let i = collectAllRegexCSS.length - 1; i >= 0; i--) {
                    try {
                        const cleanedPattern = collectAllRegexCSS[i].replace(/\\\\/g, '\\');
                        const regex = new RegExp(cleanedPattern);
                        if (regex.test($(this).find('input').val())) {
                            collectAllRegexCSS.splice(i, 1);
                        }
                    } catch (e) {
                        showToast('Invalid regex in collectAllRegexCSS: ' + collectAllRegexCSS[i] + ' - ' + e, 'error');
                    }
                }

                if(handleMatch === true || regexMatch === true){
                    $(this).find('input').prop('checked', true);
                    $(this).addClass('selected');
                }
            });

            let addintionalCSSHTML = '';
            if(collectAllRegexCSS.length){
                collectAllRegexCSS.forEach(file => {
                    addintionalCSSHTML += `
                        <div class="additional-file-item d-flex align-items-center mb-2 justify-content-between">
                            <input type="hidden" name="additional_css_files[]" value="${file.replace(/\\/g, '')}">
                            <span class="me-2">${file.replace(/\\/g, '')}</span>
                            <button class="btn btn-sm btn-outline-danger remove-file-btn">Remove</button>
                        </div>`;
                });
                $(`#additional-css-files-list`).html(addintionalCSSHTML);
            }
        }
        
        // Render JS assets with preferences
        if (assetsJS.length) {
            assetsJS.each(function() {
                const handleMatch = assets.scripts_to_remove?.includes($(this).find('.asset-handle').text());
                const regexMatch = assets.scripts_to_remove_by_regex?.some(regexString => {
                    try {
                        const cleanedPattern = regexString.replace(/\\\\/g, '\\');
                        const pattern = new RegExp(cleanedPattern);
                        return pattern.test($(this).find('input').val());
                    } catch (e) {
                        showToast('Invalid regex: ' + regexString + ' - ' + e, 'error');
                        return false;
                    }
                });

                for (let i = collectAllRegexJS.length - 1; i >= 0; i--) {
                    try {
                        const cleanedPattern = collectAllRegexJS[i].replace(/\\\\/g, '\\');
                        const regex = new RegExp(cleanedPattern);
                        if (regex.test($(this).find('input').val())) {
                            collectAllRegexJS.splice(i, 1);
                        }
                    } catch (e) {
                        showToast('Invalid regex in collectAllRegexJS: ' + collectAllRegexJS[i] + ' - ' + e, 'error');
                    }
                }
                
                if(handleMatch === true || regexMatch === true){
                    $(this).find('input').prop('checked', true);
                    $(this).addClass('selected');
                }
            });

            let addintionalJSHTML = '';
            if(collectAllRegexJS.length){
                collectAllRegexJS.forEach(file => {
                    addintionalJSHTML += `
                        <div class="additional-file-item d-flex align-items-center mb-2 justify-content-between">
                            <input type="hidden" name="additional_js_files[]" value="${file.replace(/\\/g, '')}">
                            <span class="me-2">${file.replace(/\\/g, '')}</span>
                            <button class="btn btn-sm btn-outline-danger remove-file-btn">Remove</button>
                        </div>`;
                });
                $(`#additional-js-files-list`).html(addintionalJSHTML);
            }
        }
        
        // Render JS assets with preferences
        if (assetsPlugin.length) {
            assetsPlugin.each(function() {
                const handleMatch = assets.plugins_to_remove?.includes($(this).find('input').val());                
                if(handleMatch === true){
                    $(this).find('input').prop('checked', true);
                    $(this).addClass('selected');
                }
            });
        }
    }
    
    //Update asset list
    function updateAssetList(assets) {
        let htmlCSS = '';
        
        //Render CSS assets
        if (assets.css && assets.css.length) {
            htmlCSS += `<div class="asset-list-header position-sticky top-0 z-index-2 p-3">
                <h5>CSS Assets</h5>
                <input type="text" class="asset-search form-control" placeholder="Search CSS files..." data-asset-type="css"/>
            </div>
            <div class="asset-list">
                <ul>`;
                let cssCount = 0;
                assets.css.forEach(file => {
                    const handleDisplay = file.handle === 'no-handle' 
                        ? '<span class="no-handle">(no handle)</span>' 
                        : `<span class="asset-handle">${file.handle}</span>`;
                    
                        htmlCSS += `<li class="asset-list-row" data-src="${file.src}" data-handle="${file.handle}">
                        <input id="css_${cssCount}_assets" type="checkbox" name="css_assets[]" value="${file.src}">
                        <label for="css_${cssCount}_assets">
                            <div class="asset-info d-flex flex-column">
                                ${handleDisplay}
                                <span class="asset-src">${file.src}</span>
                            </div>
                        </label>
                    </li>`;
                    cssCount++;
                });
            
            htmlCSS += '</ul></div>';
        }
        $('#asset-list-css-container').html(htmlCSS);
        
        let htmlJS = '';
        //Render JS assets
        if (assets.js && assets.js.length) {
            htmlJS += `<div class="asset-list-header position-sticky top-0 z-index-2 p-3">
                <h5>JS Assets</h5>
                <input type="text" class="asset-search form-control" placeholder="Search JS files..." data-asset-type="js"/>
            </div>
            <div class="asset-list">
                <ul>`;
                let jsCount = 0;
                assets.js.forEach(file => {
                    const handleDisplay = file.handle === 'no-handle' 
                        ? '<span class="no-handle">(no handle)</span>' 
                        : `<span class="asset-handle">${file.handle}</span>`;
                    
                        htmlJS += `<li class="asset-list-row" data-src="${file.src}" data-handle="${file.handle}">
                        <input id="js_${jsCount}_assets" type="checkbox" name="js_assets[]" value="${file.src}">
                        <label for="js_${jsCount}_assets">
                            <div class="asset-info d-flex flex-column">
                                ${handleDisplay}
                                <span class="asset-src">${file.src}</span>
                            </div>
                        </label>
                    </li>`;
                    jsCount++;
                });
            
            htmlJS += '</ul></div>';
        }
        
        $('#asset-list-js-container').html(htmlJS);
        
        // Add search functionality
        $('.asset-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const assetType = $(this).data('asset-type');
            
            $(`#asset-list-${assetType}-container li`).each(function() {
                let fullSrc = $(this).data('src');
                if(fullSrc){
                    fullSrc = String(fullSrc).toLowerCase();
                }
                const srcParts = fullSrc.split('/');
                const filename = srcParts[srcParts.length - 1];
                let handle = $(this).data('handle');
                if(handle){
                    handle = String(handle).toLowerCase();
                }
                const matches = filename.includes(searchTerm) || handle.includes(searchTerm);
                $(this).toggle(matches);
            });
        });
    }
    
    function fetchAssetsByUrl() {
        $.ajax({
            url: pageAssetsOptimizer.ajaxurl,
            type: 'POST',
            data: {
                action: 'page_assets_optimizer_get_assets',
                nonce: pageAssetsOptimizer.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#asset-list-css-container').html(response.data.css);
                    $('#asset-list-js-container').html(response.data.js);
                    $('.all-assets-button-group').removeClass('d-none');
                } else {
                    console.error('Error fetching assets:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    // Change selected list color
    $(document).on('click', '.asset-list-row label', function(event) {
        event.preventDefault();
        if($(this).parent().hasClass('selected')){
            $(this).parent().removeClass('selected');
            $(this).parent().find('input').prop('checked', false);
        }else{
            $(this).parent().addClass('selected');
            $(this).parent().find('input').prop('checked', true);
        }
    });

    // Change selected list color
    $(document).on('click', '.asset-list-row input[type="checkbox"]', function(event) {
        if($(this).parent().hasClass('selected')){
            $(this).parent().removeClass('selected');
            $(this).prop('checked', false);
        }else{
            $(this).parent().addClass('selected');
            $(this).prop('checked', true);
        }
    });
    
    // Button handlers
    $(document).on('click', '.select-all', function() {
        const type = $(this).data('type');
        $(`#asset-list-${type}-container input[type="checkbox"]`).prop('checked', true);
        $(`#asset-list-${type}-container li`).addClass('selected');
    });
    
    $(document).on('click', '.deselect-all', function() {
        const type = $(this).data('type');
        $(`#asset-list-${type}-container input[type="checkbox"]`).prop('checked', false);
        $(`#asset-list-${type}-container li`).removeClass('selected');
    });
    
    // Handle plugin selection buttons
    jQuery(document).on('click', '.select-all[data-type="plugin"], .deselect-all[data-type="plugin"]', function(e) {
        e.preventDefault();
        
        const isSelect = jQuery(this).hasClass('select-all');
        jQuery('#plugin input[name="plugin_to_remove[]"]').prop('checked', isSelect);
        
    });

    // Handle CSS/JS selection buttons (existing functionality)
    jQuery(document).on('click', '.select-all[data-type="css"], .deselect-all[data-type="css"], .select-all[data-type="js"], .deselect-all[data-type="js"]', function(e) {
        e.preventDefault();
        
        const type = jQuery(this).data('type');
        const isSelect = jQuery(this).hasClass('select-all');
        jQuery('#file-assets input[name="' + type + '_assets[]"]').prop('checked', isSelect);
        
    });
    
    //Save selection for CSS and JS assets in database
    $(document).off('click', '.save-selection, .save-plugin-selection').on('click', '.save-selection, .save-plugin-selection', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        
        const $btn = $(this);
        if ($btn.hasClass('processing')) return;
        $btn.addClass('processing');

        const pageType = $('[name="page_input_mode"]:checked').val();
        
        let pageId = '';
        if(pageType === 'custom_url'){
            pageId = $('#custom-url').val();
            if (!pageId) {
                showToast('Please enter segment of URL', 'error');
                $btn.removeClass('processing');
                return;
            }
        } else{
            pageId = $('#page-assets-selector').val();
            if (!pageId) {
                showToast('Please select a page first', 'error');
                $btn.removeClass('processing');
                return;
            }
        }
        
        // Get current selections
        const scriptsToRemove = [];
        const stylesToRemove = [];
        const scriptsToRemoveByRegex = [];
        const stylesToRemoveByRegex = [];
        const pluginsToRemove = [];
        
        $('#asset-list-css-container input:checked').each(function() {
            const $item = $(this).closest('label');
            const handle = $item.find('.asset-handle').text().trim();
            const src = $(this).val();
            
            if (handle && handle !== 'no-handle') {
                stylesToRemove.push(handle);
            } else {
                stylesToRemoveByRegex.push(src.split('/').pop().replace(/[.]/g, '\\$&'));
            }
        });

        if($('#additional-css-files-list input').length){
            $('#additional-css-files-list input').each(function() {
                const src = $(this).val();
                stylesToRemoveByRegex.push(src.split('/').pop().replace(/[.]/g, '\\$&'));
            });
        }
        
        $('#asset-list-js-container input:checked').each(function() {
            const $item = $(this).closest('label');
            const handle = $item.find('.asset-handle').text().trim();
            const src = $(this).val();
            
            if (handle && handle !== 'no-handle') {
                scriptsToRemove.push(handle);
            } else {
                scriptsToRemoveByRegex.push(src.split('/').pop().replace(/[.]/g, '\\$&'));
            }
        });
        
        if($('#additional-js-files-list input').length){
            $('#additional-js-files-list input').each(function() {
                const src = $(this).val();
                scriptsToRemoveByRegex.push(src.split('/').pop().replace(/[.]/g, '\\$&'));
            });
        }
        
        $('.plugin-optimizer input:checked').each(function() {
            const src = $(this).val();
            if (src !== 'no-handle') {
                pluginsToRemove.push(src);
            }
        });
        
        // Prepare clean data object
        const saveData = {};
        saveData[pageId] = {
            scripts_to_remove: scriptsToRemove,
            styles_to_remove: stylesToRemove,
            scripts_to_remove_by_regex: scriptsToRemoveByRegex,
            styles_to_remove_by_regex: stylesToRemoveByRegex,
            plugins_to_remove: pluginsToRemove
        };
        
        //Post data to server
        $.ajax({
            type: 'POST',
            url: pageAssetsOptimizer.ajaxurl,
            data: {
                action: 'page_assets_save_preferences',
                optimization_data: JSON.stringify(saveData),
                nonce: pageAssetsOptimizer.nonce
            },
            complete: function() {
                $btn.removeClass('processing');
            },
            success: function(response) {
                if (response.success) {
                    showToast('Preferences saved successfully!');
                } else {
                    //Show error message
                    const errorMsg = response.data?.message || response.data || 'Unknown error';
                    showToast('Error saving preferences: ' + errorMsg, 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMsg = 'Server error: ' + textStatus;
                try {
                    const responseText = jqXHR.responseText.replace(/^[^\{]*/, '');
                    const response = JSON.parse(responseText);
                    if (response.data?.message) {
                        errorMsg = response.data.message;
                    }
                } catch (e) {
                    // If parsing fails, use the raw error message
                    errorMsg = jqXHR.responseText || errorThrown;
                }
                
                showToast(errorMsg, 'error');
            }
        });
    });
    
    // Handle export success notification
    if (window.location.search.includes('exported=1')) {
        const downloadStarted = window.location.search.includes('download=1');
        
        if (downloadStarted) {
            showToast('Export completed! Your download should start automatically.', 'success');
            
            // Clean up URL
            const cleanUrl = window.location.href.split('?')[0];
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }

    function getSegmentList() {
        $.ajax({
            url: pageAssetsOptimizer.ajaxurl,
            type: 'POST',
            data: {
                action: 'page_assets_get_segment_list',
                nonce: pageAssetsOptimizer.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log(response);
                    let allSegmentData = response?.data;
                    let segmentOption = '';
                    if(allSegmentData.length > 0) {
                        for(let segmentData of allSegmentData) {
                            segmentOption = segmentOption+`<li class="auto-list segment_list px-3 mb-0 col-12 d-flex align-items-center" id="segment_list_${segmentData}"><span>${segmentData}</span></li>`;
                        }
                        $('#segment-list').html(segmentOption);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    $(document).on('click', '#custom-url-input', function (event) {
        event.stopPropagation();
    });

    $(document).on('click', '#custom-url', function (event) {
        event.stopPropagation();
        if($('#segment-list li').length > 0){
            $('#suggest-segment-list').removeClass('d-none');
            $('#suggest-segment-list').addClass('d-inline-flex');
            setTimeout(() => {
                //searchSegmentName();
            }, 100);
        }
    });

    $(document).on('click', '.segment_list', function () {
        setActiveSegment($(this).text());
    });

    $(document).on('click', '#suggest-segment-list', function (event) {
        event.stopPropagation();
    });

    function hideListBox () {
        $('.auto-suggest-segment-list').removeClass('d-inline-flex');
        $('.auto-suggest-segment-list').addClass('d-none');
    }

    function setActiveSegment (name) {
        $('#custom-url').val(name);
        loadAssetsWithPreferences(name);
        hideListBox ();
    }

	$(document).on('click', 'body', function (event) {
        console.log(event);
        setTimeout(() => {
            if($('.auto-suggest-segment-list').hasClass('d-inline-flex')){
                hideListBox ();
            }
        }, 100);
	});

    function searchSegmentName () {
        $('input#segment_name_search').quicksearch('#segment-list li', {
            'delay': 300,
            'selector': 'span',
            'stripeRows': ['auto-list'],
            'bind': 'keyup click input',
            'show': function () {
                this.style.display = 'inline-flex';
            },
            'hide': function () {
                this.style.display = 'none';
            },
            'prepareQuery': function (val) {
                return new RegExp(val, "i");
            },
            'testQuery': function (query, txt, _row) {
                return query.test(txt);
            }
        });
    }
    
    // Additional files functionality
    $(document).on('click', '.add-file-btn', function () {
        const input = $(this).siblings('.additional-file-input');
        const fileType = $(this).data('type');
        let filePath = input.val().trim();
    
        if (!filePath) {
            showToast('Please enter a file name', 'error');
            return;
        }
    
        // Remove query string or hash
        filePath = filePath.split('?')[0].split('#')[0];
    
        // Validate extension
        const expectedExt = fileType === 'css' ? '.css' : '.js';
        if (!filePath.endsWith(expectedExt)) {
            showToast(`File must end with ${expectedExt}`, 'error');
            return;
        }
    
        // Prevent paths or full URLs
        const isFullPath = /^(https?:\/\/|\/|\.\/|[\w\-]+\/)/i.test(filePath);
        if (isFullPath) {
            showToast('Please enter only the file name, not a path or URL', 'error');
            return;
        }
    
        // Check for duplicates
        const isDuplicate = $(`#additional-${fileType}-files-list input`)
            .toArray()
            .some(el => $(el).val().trim() === filePath);
    
        if (isDuplicate) {
            showToast(`This ${fileType.toUpperCase()} file is already added`, 'error');
            return;
        }
    
        // Append valid file
        $(`#additional-${fileType}-files-list`).append(`
            <div class="additional-file-item d-flex align-items-center mb-2 justify-content-between">
                <input type="hidden" name="additional_${fileType}_files[]" value="${filePath}">
                <span class="me-2">${filePath}</span>
                <button class="btn btn-sm btn-outline-danger remove-file-btn">Remove</button>
            </div>
        `);
    
        input.val('');
    });

    $(document).on('click', '.remove-file-btn', function() {
        $(this).closest('.additional-file-item').remove();
    });

    // Create toast container if it doesn't exist
    if (!document.getElementById('pageAssetsToastContainer')) {
        const toastContainer = document.createElement('div');
        toastContainer.id = 'pageAssetsToastContainer';
        toastContainer.className = 'position-fixed top-0 end-0 p-3 mt-4';
        toastContainer.style.zIndex = '1100';
        document.body.appendChild(toastContainer);
    }

    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('pageAssetsToastContainer');
        if(type === 'error'){
            type = 'danger';
        }
        
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastEl);
        
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 3000
        });
        
        toast.show();
        
        // Remove toast after it's hidden
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    }

    // Image optimization toggle handler
    $(document).on('click', '.optimize-images', function() {
        const isEnabled = $('#image-optimization-toggle').is(':checked');
        if(isEnabled){
            $.ajax({
                url: pageAssetsOptimizer.ajaxurl,
                type: 'POST',
                data: {
                    action: 'page_assets_save_image_optimization',
                    enabled: isEnabled ? 1 : 0,
                    nonce: pageAssetsOptimizer.nonce
                },
                success: function(response) {
                    console.log(response);
                    if (!response.success) {
                        showToast('Failed to save image optimization preference', 'error');
                    } else {
                        showToast('Image optimization enabled successfully!', 'success');
                    }
                },
                error: function(xhr, status, error) {
                    showToast('AJAX error:', error, 'error');
                }
            });
        } else {
            showToast('Image optimization disabled successfully!', 'success');
        }
    });

    // Image optimization toggle handler
    $(document).on('click', '.optimize-minify', function() {
        const isEnabledCss = $('#minify-css-toggle').is(':checked');
        const isEnabledJs = $('#minify-js-toggle').is(':checked');
        if(isEnabledCss || isEnabledJs){
            $.ajax({
                url: pageAssetsOptimizer.ajaxurl,
                type: 'POST',
                data: {
                    action: 'page_assets_save_minify',
                    enabledCss: isEnabledCss ? 1 : 0,
                    enabledJs: isEnabledJs ? 1 : 0,
                    nonce: pageAssetsOptimizer.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        showToast('Failed to save minification preference', 'error');
                    } else {
                        showToast('Minification enabled successfully!', 'success');
                    }
                },
                error: function(xhr, status, error) {
                    showToast('AJAX error:', error, 'error');
                }
            });
        } else {
            showToast('Minification disabled successfully!', 'success');
        }
    });

    function getAssetsPreferences(){
        $.ajax({
            url: pageAssetsOptimizer.ajaxurl,
            type: 'POST',
            data: {
                action: 'page_settings_get_preferences',
                nonce: pageAssetsOptimizer.nonce
            },
            success: function(response) {
                console.log(response);
                if (!response.success) {
                    showToast('Failed to get preferences', 'error');
                } else {
                    if(response.data){
                        if(response.data[0].image_optimization==1){
                            $('#image-optimization-toggle').prop('checked', true);
                            $('#image-optimization-status .toggle-switch').addClass('active-icon');
                        }else{
                            $('#image-optimization-toggle').prop('checked', false);
                            $('#image-optimization-status .toggle-switch').removeClass('active-icon');
                        }
                        if(response.data[0].minify_css==1){
                            $('#minify-css-toggle').prop('checked', true);
                            $('#css-minification-status .toggle-switch').addClass('active-icon');
                        }else{
                            $('#minify-css-toggle').prop('checked', false);
                            $('#css-minification-status .toggle-switch').removeClass('active-icon');
                        }
                        if(response.data[0].minify_js==1){
                            $('#minify-js-toggle').prop('checked', true);
                            $('#js-minification-status .toggle-switch').addClass('active-icon');
                        }else{
                            $('#minify-js-toggle').prop('checked', false);
                            $('#js-minification-status .toggle-switch').removeClass('active-icon');
                        }
                    }
                }
            },
            error: function(xhr, status, error) {
                showToast('AJAX error:', error, 'error');
            }
        });
    }
});
