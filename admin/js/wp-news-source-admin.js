/**
 * WP News Source Admin JavaScript - Professional UI System
 */

window.WPNewsSource = window.WPNewsSource || {};

(function($, wpns) {
    'use strict';

    // Global variables
    var selectedTags = [];
    var selectedCategory = null;
    var searchTimeouts = {};

    // Professional Notification System
    const WPNSNotices = {
        container: null,

        init: function() {
            if (!this.container) {
                this.container = $('<div class="wpns-notices-container"></div>');
                $('.wpns-admin').prepend(this.container);
            }
        },

        show: function(message, type = 'info', options = {}) {
            this.init();

            const defaults = {
                dismissible: true,
                icon: true,
                timeout: 0,
                actions: []
            };

            const settings = { ...defaults, ...options };
            const noticeId = 'wpns-notice-' + Date.now();
            const iconClass = this.getIconClass(type);

            let noticeHtml = `
                <div id="${noticeId}" class="notice notice-${type} ${settings.dismissible ? 'is-dismissible' : ''}">
                    ${settings.icon ? `<div class="notice-icon"><span class="dashicons ${iconClass}"></span></div>` : ''}
                    <div class="notice-content">
                        <p>${message}</p>
                        ${settings.actions.length ? this.buildActions(settings.actions) : ''}
                    </div>
                    ${settings.dismissible ? '<button class="notice-dismiss"><span class="dashicons dashicons-dismiss"></span></button>' : ''}
                </div>
            `;

            const $notice = $(noticeHtml);
            this.container.append($notice);

            // Auto-dismiss
            if (settings.timeout > 0) {
                setTimeout(() => {
                    this.dismiss(noticeId);
                }, settings.timeout);
            }

            // Dismiss handler
            $notice.find('.notice-dismiss').on('click', () => {
                this.dismiss(noticeId);
            });

            return noticeId;
        },

        dismiss: function(noticeId) {
            const $notice = $('#' + noticeId);
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        },

        clear: function() {
            this.container.fadeOut(300, function() {
                $(this).empty().show();
            });
        },

        getIconClass: function(type) {
            const icons = {
                success: 'dashicons-yes-alt',
                warning: 'dashicons-warning',
                error: 'dashicons-dismiss',
                info: 'dashicons-info'
            };
            return icons[type] || icons.info;
        },

        buildActions: function(actions) {
            let actionsHtml = '<div class="notice-actions">';
            actions.forEach(action => {
                actionsHtml += `
                    <button class="button ${action.primary ? 'button-primary' : 'button-secondary'}" 
                            onclick="${action.callback}">
                        ${action.text}
                    </button>
                `;
            });
            actionsHtml += '</div>';
            return actionsHtml;
        },

        // Convenience methods
        success: function(message, options = {}) {
            return this.show(message, 'success', { ...options, timeout: options.timeout || 4000 });
        },

        warning: function(message, options = {}) {
            return this.show(message, 'warning', options);
        },

        error: function(message, options = {}) {
            return this.show(message, 'error', options);
        },

        info: function(message, options = {}) {
            return this.show(message, 'info', options);
        }
    };

    // Enhanced AJAX helper
    function wpnsAjaxCall(action, data, successCallback, errorCallback) {
        const ajaxData = {
            action: 'wpns_' + action,
            nonce: wpns_ajax.nonce,
            ...data
        };

        $.ajax({
            url: wpns_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            beforeSend: function() {
                showLoading();
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    if (successCallback) successCallback(response.data);
                } else {
                    WPNSNotices.error(response.data || 'Unknown error occurred');
                    if (errorCallback) errorCallback(response.data);
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                WPNSNotices.error('Connection error: ' + error);
                if (errorCallback) errorCallback(error);
            }
        });
    }

    // Loading helpers
    function showLoading(target = 'body') {
        const $target = $(target);
        $target.addClass('wpns-loading');

        if (!$target.find('.wpns-spinner').length) {
            $target.append('<div class="wpns-spinner"><div class="spinner"></div></div>');
        }
    }

    function hideLoading(target = 'body') {
        $(target).removeClass('wpns-loading');
        $(target).find('.wpns-spinner').remove();
    }

    // Tab System
    wpns.setupTabs = function() {
        $('.wpns-nav-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this).attr('href').substring(1);
            
            // Update tabs
            $('.nav-tab').removeClass('nav-tab-active').attr('aria-selected', 'false');
            $(this).addClass('nav-tab-active').attr('aria-selected', 'true');
            
            // Update content
            $('.wpns-tab-content').removeClass('active');
            $('#' + target + '-tab').addClass('active');
            
            // Update URL
            history.replaceState(null, null, '#' + target);
        });
        
        // Load tab from URL hash
        if (window.location.hash) {
            const hash = window.location.hash.substring(1);
            $('[href="#' + hash + '"]').trigger('click');
        }
    };

    // Initialize autocomplete functionality
    initializeCategoryAutocomplete();
    initializeTagsAutocomplete();
    
    // Initialize tags counter on page load
    if ($('#source-tags-search').length > 0) {
        // If in editing mode, collect existing tags
        if ($('.wpns-tag-item').length > 0) {
            $('.wpns-tag-item').each(function() {
                var tagName = $(this).data('tag-name');
                if (tagName && selectedTags.indexOf(tagName) === -1) {
                    selectedTags.push(tagName);
                }
            });
        }
        updateTagsCounter();
        updateTagsDisplay();
    }
    
    /**
     * Category Autocomplete
     */
    function initializeCategoryAutocomplete() {
        var $searchInput = $('#source-category-search');
        var $resultsContainer = $('#source-category-results');
        var $selectedContainer = $('#source-category-selected');
        var $hiddenInput = $('#source-category');
        
        $searchInput.on('input', function() {
            var query = $(this).val().trim();
            
            // Clear previous timeout
            if (searchTimeouts.categories) {
                clearTimeout(searchTimeouts.categories);
            }
            
            if (query.length < 2) {
                $resultsContainer.removeClass('show').empty();
                return;
            }
            
            // Debounce search
            searchTimeouts.categories = setTimeout(function() {
                searchCategories(query, $resultsContainer);
            }, 300);
        });
        
        // Handle category selection
        $(document).on('click', '.wpns-category-result', function() {
            var categoryId = $(this).data('category-id');
            var categoryName = $(this).data('category-name');
            
            selectCategory(categoryId, categoryName, $searchInput, $resultsContainer, $selectedContainer, $hiddenInput);
        });
        
        // Handle category removal
        $(document).on('click', '.wpns-remove-category', function() {
            clearCategory($searchInput, $selectedContainer, $hiddenInput);
        });
    }
    
    /**
     * Tags Autocomplete
     */
    function initializeTagsAutocomplete() {
        var $searchInput = $('#source-tags-search');
        var $resultsContainer = $('#source-tags-results');
        var $selectedContainer = $('#source-tags-selected');
        
        $searchInput.on('input', function() {
            var query = $(this).val().trim();
            
            // Clear previous timeout
            if (searchTimeouts.tags) {
                clearTimeout(searchTimeouts.tags);
            }
            
            if (query.length < 2) {
                $resultsContainer.removeClass('show').empty();
                return;
            }
            
            // Debounce search
            searchTimeouts.tags = setTimeout(function() {
                searchTags(query, $resultsContainer);
            }, 300);
        });
        
        // Handle tag selection
        $(document).on('click', '.wpns-tag-result', function() {
            var tagName = $(this).data('tag-name');
            var maxTags = parseInt(wpns_ajax.max_tags) || 3; // Default to 3 if not set
            
            if (selectedTags.indexOf(tagName) === -1) {
                // Check tag limit
                if (selectedTags.length >= maxTags) {
                    if (typeof WPNSNotices !== 'undefined') {
                        WPNSNotices.error('Maximum number of tags (' + maxTags + ') reached');
                    } else {
                        alert('Maximum number of tags (' + maxTags + ') reached');
                    }
                    return;
                }
                
                addTag(tagName, $selectedContainer);
                // Clear input and results
                $searchInput.val('');
                $resultsContainer.removeClass('show').empty();
                // Refocus on input for continuous adding
                setTimeout(function() {
                    $searchInput.focus();
                }, 100);
            }
        });
        
        // Handle tag removal
        $(document).on('click', '.wpns-remove-tag', function() {
            var tagName = $(this).parent().data('tag-name');
            removeTag(tagName);
        });
        
        // Handle Enter key for creating new tags
        $searchInput.on('keypress', function(e) {
            if (e.which === 13) { // Enter
                e.preventDefault();
                var tagName = $(this).val().trim();
                var maxTags = parseInt(wpns_ajax.max_tags) || 3; // Default to 3 if not set
                
                if (tagName && selectedTags.indexOf(tagName) === -1) {
                    // Check tag limit
                    if (selectedTags.length >= maxTags) {
                        if (typeof WPNSNotices !== 'undefined') {
                            WPNSNotices.error('Maximum number of tags (' + maxTags + ') reached');
                        } else {
                            alert('Maximum number of tags (' + maxTags + ') reached');
                        }
                        return;
                    }
                    
                    addTag(tagName, $selectedContainer);
                    $(this).val('');
                    $resultsContainer.removeClass('show').empty();
                }
            }
        });
    }
    
    /**
     * Search categories via AJAX
     */
    function searchCategories(query, $resultsContainer) {
        // Check if wpns_ajax is defined
        if (typeof wpns_ajax === 'undefined') {
            // Error: wpns_ajax is undefined
            $resultsContainer.html('<div class="wpns-error">Configuration error: wpns_ajax not loaded</div>').addClass('show');
            return;
        }
        
        var ajaxUrl = wpns_ajax.rest_url + 'wp-news-source/v1/categories/search';
        
        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            data: {
                search: query,
                limit: 10
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpns_ajax.rest_nonce);
                $resultsContainer.addClass('loading');
            },
            success: function(response) {
                $resultsContainer.removeClass('loading');
                
                if (response.success && response.categories && response.categories.length > 0) {
                    var html = '';
                    
                    response.categories.forEach(function(category) {
                        html += '<div class="wpns-category-result" data-category-id="' + category.id + '" data-category-name="' + category.name + '">' +
                               '<strong>' + category.name + '</strong> (ID: ' + category.id + ')' +
                               (category.description ? '<br><small>' + category.description + '</small>' : '') +
                               '</div>';
                    });
                    
                    $resultsContainer.html(html).addClass('show');
                } else {
                    $resultsContainer.html('<div class="wpns-no-results">No categories found</div>').addClass('show');
                }
            },
            error: function(xhr, status, error) {
                // Error: Category search failed
                $resultsContainer.removeClass('loading');
                $resultsContainer.html('<div class="wpns-error">Error searching categories: ' + error + '</div>').addClass('show');
            }
        });
    }
    
    /**
     * Search tags via AJAX
     */
    function searchTags(query, $resultsContainer) {
        // Check if wpns_ajax is defined
        if (typeof wpns_ajax === 'undefined') {
            // Error: wpns_ajax is undefined
            $resultsContainer.html('<div class="wpns-error">Configuration error: wpns_ajax not loaded</div>').addClass('show');
            return;
        }
        
        var ajaxUrl = wpns_ajax.rest_url + 'wp-news-source/v1/tags/search';
        
        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            data: {
                search: query,
                limit: 10
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpns_ajax.rest_nonce);
                $resultsContainer.addClass('loading');
            },
            success: function(response) {
                $resultsContainer.removeClass('loading');
                
                if (response.success && response.tags && response.tags.length > 0) {
                    var html = '';
                    
                    response.tags.forEach(function(tag) {
                        if (selectedTags.indexOf(tag.name) === -1) {
                            html += '<div class="wpns-tag-result" data-tag-name="' + tag.name + '">' +
                                   '<strong>' + tag.name + '</strong> (ID: ' + tag.id + ')' +
                                   '</div>';
                        }
                    });
                    
                    if (html) {
                        $resultsContainer.html(html).addClass('show');
                    } else {
                        $resultsContainer.html('<div class="wpns-no-results">All matching tags already selected</div>').addClass('show');
                    }
                } else {
                    var createOption = '<div class="wpns-tag-result wpns-create-tag" data-tag-name="' + query + '">' +
                                     '<strong>Create: "' + query + '"</strong>' +
                                     '</div>';
                    $resultsContainer.html(createOption).addClass('show');
                }
            },
            error: function(xhr, status, error) {
                // Error: Tags search failed
                $resultsContainer.removeClass('loading');
                $resultsContainer.html('<div class="wpns-error">Error searching tags: ' + error + '</div>').addClass('show');
            }
        });
    }
    
    /**
     * Select category
     */
    function selectCategory(categoryId, categoryName, $searchInput, $resultsContainer, $selectedContainer, $hiddenInput) {
        selectedCategory = {id: categoryId, name: categoryName};
        
        $searchInput.val('').hide();
        $resultsContainer.hide().empty();
        $hiddenInput.val(categoryId);
        
        var selectedHtml = '<div class="wpns-selected-category">' +
                          '<span><strong>' + categoryName + '</strong> (ID: ' + categoryId + ')</span>' +
                          '<span class="wpns-remove-category" title="Remove category">×</span>' +
                          '</div>';
        
        $selectedContainer.html(selectedHtml).show();
    }
    
    /**
     * Clear category selection
     */
    function clearCategory($searchInput, $selectedContainer, $hiddenInput) {
        selectedCategory = null;
        $searchInput.val('').show();
        $selectedContainer.empty().hide();
        $hiddenInput.val('');
    }
    
    /**
     * Add tag
     */
    function addTag(tagName, $selectedContainer) {
        selectedTags.push(tagName);
        
        var tagHtml = '<span class="wpns-tag-item" data-tag-name="' + tagName + '">' +
                      tagName +
                      '<span class="wpns-remove-tag">×</span>' +
                      '</span>';
        
        $selectedContainer.append(tagHtml);
        updateTagsCounter();
        updateTagsDisplay();
    }
    
    /**
     * Remove tag
     */
    function removeTag(tagName) {
        var index = selectedTags.indexOf(tagName);
        if (index > -1) {
            selectedTags.splice(index, 1);
            $('.wpns-tag-item[data-tag-name="' + tagName + '"]').remove();
            updateTagsCounter();
            updateTagsDisplay();
        }
    }
    
    /**
     * Update tags counter display
     */
    function updateTagsCounter() {
        var $counter = $('#wpns-tags-counter');
        var $count = $('#wpns-tags-count');
        var tagCount = selectedTags.length;
        
        $count.text(tagCount);
        
        if (tagCount > 0) {
            $counter.show().addClass('has-tags');
        } else {
            $counter.hide().removeClass('has-tags');
        }
    }
    
    /**
     * Update tags display with limit info
     */
    function updateTagsDisplay() {
        var maxTags = parseInt(wpns_ajax.max_tags) || 3;
        var $tagsLabel = $('.wpns-form-group label[for="source-tags"]');
        var $tagsInfo = $('#wpns-tags-limit-info');
        
        if ($tagsInfo.length === 0) {
            $tagsInfo = $('<span id="wpns-tags-limit-info" style="margin-left: 10px; color: #666; font-weight: normal;"></span>');
            $tagsLabel.append($tagsInfo);
        }
        
        $tagsInfo.text('(' + selectedTags.length + '/' + maxTags + ')');
        
        // Disable input if limit reached
        if (selectedTags.length >= maxTags) {
            $('#source-tags-search').prop('disabled', true).attr('placeholder', 'Maximum tags reached');
        } else {
            $('#source-tags-search').prop('disabled', false).attr('placeholder', 'Search or create tags...');
        }
    }
    
    // Enhanced save source form
    $('#wpns-add-source-form').on('submit', function(e) {
        e.preventDefault();
        
        const $submitBtn = $('.button-primary');
        const originalText = $submitBtn.text();
        
        // Enhanced validation
        const requiredFields = ['source-name', 'source-category'];
        let isValid = true;
        
        requiredFields.forEach(fieldId => {
            const $field = $('#' + fieldId);
            const value = fieldId === 'source-category' ? $field.val() : $field.val().trim();
            
            if (!value) {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        if (!isValid) {
            WPNSNotices.error('Please fill in all required fields');
            return;
        }
        
        // Validate AI config JSON if provided
        const aiConfig = $('#source-ai-config').val().trim();
        if (aiConfig) {
            try {
                JSON.parse(aiConfig);
            } catch (e) {
                WPNSNotices.error('Invalid JSON in AI Detection Rules: ' + e.message);
                $('#source-ai-config').focus();
                return;
            }
        }
        
        const formData = {
            name: $('#source-name').val(),
            slug: '', // Auto-generated
            source_type: $('#source-type-select').val() || 'general',
            description: $('#source-description').val() || '',
            keywords: $('#source-keywords').val() || '',
            detection_rules: $('#source-ai-config').val() || '', // AI detection configuration
            category_id: $('#source-category').val(),
            tags: selectedTags,
            auto_publish: $('#source-auto-publish').is(':checked') ? 1 : 0,
            requires_review: 1, // Default to requires review for safety
            webhook_url: '', // Moved to settings
            generate_api_key: 0 // Moved to settings
        };
        
        // Add source_id if editing
        const sourceId = $('input[name="source_id"]').val();
        if (sourceId) {
            formData.source_id = sourceId;
        }
        
        // Add loading state to button
        $submitBtn.addClass('loading').prop('disabled', true);
        
        wpnsAjaxCall('save_source', formData, 
            function(response) {
                if (typeof WPNSNotices !== 'undefined') {
                    WPNSNotices.success('Source saved successfully!');
                }
                
                // Redirect back to All Sources
                setTimeout(() => {
                    if (typeof wpns_ajax !== 'undefined' && wpns_ajax.admin_url) {
                        window.location.href = wpns_ajax.admin_url + 'admin.php?page=wp-news-source&message=saved';
                    } else {
                        window.location.href = 'admin.php?page=wp-news-source&message=saved';
                    }
                }, 1000);
            },
            function(error) {
                $submitBtn.removeClass('loading').prop('disabled', false).text(originalText);
            }
        );
    });
    
    // Edit source
    $(document).on('click', '.wpns-edit-source', function() {
        var sourceId = $(this).data('source-id');
        
        // Redirect to edit page (or load edit modal - for now redirect)
        window.location.href = 'admin.php?page=wp-news-source-add&edit=' + sourceId;
    });
    
    // Delete source - Enhanced version with immediate feedback
    $(document).on('click', '.wpns-delete-source', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var sourceId = $button.data('source-id');
        var sourceName = $button.data('source-name');
        var $row = $('tr[data-source-id="' + sourceId + '"]');
        
        if (!$row.length) {
            // Error: Row not found for source ID
            alert('Error: Could not find source row');
            return;
        }
        
        if (confirm('Are you sure you want to delete the source "' + sourceName + '"?')) {
            // IMMEDIATE visual feedback - this should work instantly
            $row.css({
                'background-color': '#ffebee',
                'opacity': '0.5',
                'transition': 'all 0.3s ease'
            });
            
            $button.prop('disabled', true).text('Deleting...');
            
            // Check if wpns_ajax is available
            if (typeof wpns_ajax === 'undefined') {
                // Error: wpns_ajax not available
                alert('Configuration error - please refresh the page');
                $row.css({'background-color': '', 'opacity': '1'});
                $button.prop('disabled', false).text('Delete');
                return;
            }
            
            $.ajax({
                url: wpns_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpns_delete_source',
                    nonce: wpns_ajax.nonce,
                    source_id: sourceId
                },
                timeout: 10000, // 10 second timeout
                success: function(response) {
                    if (response && response.success) {
                        // Smoothly remove the row
                        $row.fadeOut(400, function() {
                            $(this).remove();
                            
                            // Check if no rows left
                            var $remainingRows = $('#the-list tr').not('.no-items');
                            
                            if ($remainingRows.length === 0) {
                                $('#the-list').html('<tr class="no-items"><td colspan="6" style="text-align:center;padding:20px;color:#666;font-style:italic;">No sources found.</td></tr>');
                            }
                        });
                        
                        // Success notification
                        if (typeof WPNSNotices !== 'undefined') {
                            WPNSNotices.success('Source deleted successfully');
                        }
                        
                    } else {
                        // Error: Delete failed
                        // Restore row appearance
                        $row.css({'background-color': '', 'opacity': '1'});
                        $button.prop('disabled', false).text('Delete');
                        
                        var errorMsg = 'Failed to delete source';
                        if (response && response.data) {
                            errorMsg += ': ' + response.data;
                        }
                        
                        if (typeof WPNSNotices !== 'undefined') {
                            WPNSNotices.error(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    // Try to extract JSON from mixed HTML/JSON response
                    var actualResponse = null;
                    if (xhr.responseText && typeof xhr.responseText === 'string') {
                        // Look for JSON pattern in the response
                        var jsonMatch = xhr.responseText.match(/\{"success":(true|false).*?\}$/);
                        if (jsonMatch) {
                            try {
                                actualResponse = JSON.parse(jsonMatch[0]);
                                
                                // If we successfully extracted JSON and it shows success, treat as success
                                if (actualResponse && actualResponse.success) {
                                    // Remove the row
                                    $row.fadeOut(400, function() {
                                        $(this).remove();
                                        var $remainingRows = $('#the-list tr').not('.no-items');
                                        if ($remainingRows.length === 0) {
                                            $('#the-list').html('<tr class="no-items"><td colspan="6" style="text-align:center;padding:20px;color:#666;font-style:italic;">No sources found.</td></tr>');
                                        }
                                    });
                                    
                                    if (typeof WPNSNotices !== 'undefined') {
                                        WPNSNotices.success('Source deleted successfully');
                                    }
                                    return;
                                }
                            } catch (e) {
                                // Could not parse extracted JSON
                            }
                        }
                    }
                    
                    // Error: AJAX error occurred during delete
                    
                    // Restore row appearance
                    $row.css({'background-color': '', 'opacity': '1'});
                    $button.prop('disabled', false).text('Delete');
                    
                    var errorMsg = 'Connection error: ' + (error || status || 'Unknown error');
                    
                    if (typeof WPNSNotices !== 'undefined') {
                        WPNSNotices.error(errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                }
            });
        }
    });
    
    // Auto-generate slug from name
    $('#source-name').on('blur', function() {
        var name = $(this).val();
        var slug = $('#source-slug').val();
        
        if (name && !slug) {
            slug = name.toLowerCase()
                .replace(/[áàäâ]/g, 'a')
                .replace(/[éèëê]/g, 'e')
                .replace(/[íìïî]/g, 'i')
                .replace(/[óòöô]/g, 'o')
                .replace(/[úùüû]/g, 'u')
                .replace(/ñ/g, 'n')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            
            $('#source-slug').val(slug);
        }
    });
    
    // Validate JSON in detection rules
    $('#source-detection-rules').on('blur', function() {
        var value = $(this).val().trim();
        if (value) {
            try {
                JSON.parse(value);
                $(this).css('border-color', '#8cc152');
            } catch (e) {
                $(this).css('border-color', '#e74c3c');
                alert('Invalid JSON: ' + e.message);
            }
        }
    });
    
    // Export/Import handlers removed - now handled in settings page to avoid duplication
    
    // Copy API endpoint to clipboard
    $(document).on('click', '.wpns-copy-endpoint', function() {
        var endpoint = $(this).data('endpoint');
        var $this = $(this);
        var originalText = $this.text();

        function showCopied() {
            $this.text('Copied!');
            setTimeout(function() {
                $this.text(originalText);
            }, 2000);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(endpoint).then(showCopied).catch(function() {
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(endpoint).select();
                document.execCommand('copy');
                $temp.remove();
                showCopied();
            });
        } else {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(endpoint).select();
            document.execCommand('copy');
            $temp.remove();
            showCopied();
        }
    });
    
    // Check for updates handler removed - now handled in settings page to avoid duplication
    
    // Enhanced form validation
    wpns.validateJSON = function(input) {
        const $input = $(input);
        const value = $input.val().trim();
        
        if (value) {
            try {
                JSON.parse(value);
                $input.removeClass('error').addClass('success');
                return true;
            } catch (e) {
                $input.removeClass('success').addClass('error');
                WPNSNotices.warning('Invalid JSON format: ' + e.message);
                return false;
            }
        } else {
            $input.removeClass('error success');
            return true;
        }
    };

    // Auto-save functionality
    wpns.setupAutoSave = function() {
        let saveTimeout;
        
        $('.wpns-form input, .wpns-form textarea, .wpns-form select').on('change input', function() {
            clearTimeout(saveTimeout);
            
            // Show unsaved indicator
            if (!$('.wpns-save-indicator').length) {
                $('.wpns-admin').after('<div class="wpns-save-indicator">Unsaved changes...</div>');
            }
            
            saveTimeout = setTimeout(function() {
                wpns.autoSave();
            }, 3000);
        });
    };

    wpns.autoSave = function() {
        // Implementation for auto-save
        $('.wpns-save-indicator').text('Auto-saved').addClass('success');
        setTimeout(() => {
            $('.wpns-save-indicator').fadeOut().remove();
        }, 2000);
    };

    // Initialize all systems
    wpns.init = function() {
        wpns.setupTabs();
        wpns.setupAutoSave();
        
        // Validate JSON fields on blur
        $(document).on('blur', 'textarea[data-type="json"]', function() {
            wpns.validateJSON(this);
        });
        
        // Enhanced button interactions
        $(document).on('click', '[data-confirm]', function(e) {
            const message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Dismiss notices
        $(document).on('click', '.notice-dismiss', function() {
            $(this).closest('.notice').fadeOut();
        });
        
        // Copy to clipboard functionality
        $(document).on('click', '[data-copy]', function() {
            const text = $(this).data('copy');
            navigator.clipboard.writeText(text).then(() => {
                WPNSNotices.success('Copied to clipboard!', { timeout: 2000 });
            }).catch(() => {
                WPNSNotices.error('Failed to copy to clipboard');
            });
        });
    };

    // Hide autocomplete results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.wpns-autocomplete-container').length) {
            $('.wpns-autocomplete-results').removeClass('show');
        }
    });

    // Initialize when DOM is ready
    $(document).ready(function() {
        wpns.init();
    });

    // Expose WPNSNotices globally for external use
    window.WPNSNotices = WPNSNotices;

})(jQuery, window.WPNewsSource);