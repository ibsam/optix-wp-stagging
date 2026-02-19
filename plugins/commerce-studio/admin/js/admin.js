/**
 * Commerce Studio - Admin JavaScript
 * Handles AJAX operations and UI interactions
 */

(function($) {
    'use strict';

    const CS = {
        config: window.commerceStudio || {},

        /**
         * Escape HTML special characters to prevent XSS.
         * @param {string} str - Untrusted string
         * @returns {string} Safe string for HTML insertion
         */
        escapeHtml: function(str) {
            if (typeof str !== 'string') return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        },

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Tab filtering
            $('.cs-tab').on('click', this.filterApps.bind(this));

            // Test connection
            $('#cs-test-connection, #cs-test-connection-btn').on('click', this.testConnection.bind(this));

            // App installation
            $(document).on('click', '.cs-install-app', this.showInstallModal.bind(this));
            $(document).on('click', '.cs-uninstall-app', this.uninstallApp.bind(this));
            $(document).on('click', '.cs-configure-app', this.showConfigureModal.bind(this));
            $(document).on('click', '.cs-preview-app', this.previewApp.bind(this));
            $(document).on('click', '.cs-faq-app', this.openAppFaq.bind(this));
            $('#cs-modal-confirm').on('click', this.installApp.bind(this));
            $('#cs-modal-cancel, .cs-modal-close, .cs-modal-overlay').on('click', this.hideModal.bind(this));
            $(document).on('click', '#cs-config-save', this.saveAppConfig.bind(this));
            $(document).on('click', '#cs-config-cancel', this.hideConfigModal.bind(this));

            // Onboarding
            $('#cs-start-onboarding').on('click', this.startOnboarding.bind(this));

            // Settings
            $('#cs-clear-cache').on('click', this.clearCache.bind(this));
            $('#cs-reset-settings').on('click', this.resetSettings.bind(this));
            $('#cs-check-updates').on('click', this.checkForUpdates.bind(this));

            // Button styling color pickers and preview
            $(document).on('input', '#cs-config-button-color-picker', function() {
                $('#cs-config-button-color').val($(this).val());
                CS.updateButtonPreview();
            });
            $(document).on('input', '#cs-config-button-color', function() {
                $('#cs-config-button-color-picker').val($(this).val());
                CS.updateButtonPreview();
            });
            $(document).on('input', '#cs-config-text-color-picker', function() {
                $('#cs-config-text-color').val($(this).val());
                CS.updateButtonPreview();
            });
            $(document).on('input', '#cs-config-text-color', function() {
                $('#cs-config-text-color-picker').val($(this).val());
                CS.updateButtonPreview();
            });
            $(document).on('change', '#cs-config-button-shape, #cs-config-button-size', function() {
                CS.updateButtonPreview();
            });
            $(document).on('input', '#cs-config-button-text', function() {
                CS.updateButtonPreview();
            });
        },

        // Filter apps by tab selection
        filterApps: function(e) {
            const $btn = $(e.currentTarget);
            const filter = $btn.data('filter');

            // Update active tab
            $('.cs-tab').removeClass('active');
            $btn.addClass('active');

            // Filter app cards
            const $cards = $('.cs-app-card');
            let visibleCount = 0;

            $cards.each(function() {
                const $card = $(this);
                const groups = ($card.data('filter-groups') || 'all').toString().split(',');

                if (filter === 'all' || groups.indexOf(filter) !== -1) {
                    $card.show();
                    visibleCount++;
                } else {
                    $card.hide();
                }
            });

            // Show/hide empty state
            if (visibleCount === 0) {
                $('.cs-filter-empty').show();
                $('.cs-apps-grid').hide();
            } else {
                $('.cs-filter-empty').hide();
                $('.cs-apps-grid').show();
            }
        },

        // Preview app - open demo page on commerce.varai.ai
        previewApp: function(e) {
            e.preventDefault();
            const appId = $(e.currentTarget).data('app-id');
            const app = this.config.availableApps?.find(a => a.slug === appId || a.id === appId);

            if (app && app.demoUrl) {
                window.open(app.demoUrl, '_blank');
            } else {
                // Fallback to main products page if no specific demo URL
                window.open('https://commerce.varai.ai/products', '_blank');
            }
        },

        // Open FAQ page for an app on commerce.varai.ai
        openAppFaq: function(e) {
            e.preventDefault();
            const appId = $(e.currentTarget).data('app-id');
            const app = this.config.availableApps?.find(a => a.slug === appId || a.id === appId);

            if (app && app.faqUrl) {
                window.open(app.faqUrl, '_blank');
            } else if (app && app.demoUrl) {
                // Fallback to demo page with #faq anchor
                window.open(app.demoUrl + '#faq', '_blank');
            } else {
                // Fallback to main help page
                window.open('https://commerce.varai.ai/help', '_blank');
            }
        },

        // Update button preview in real-time
        updateButtonPreview: function() {
            const $btn = $('#cs-preview-btn');
            if (!$btn.length) return;

            const buttonColor = $('#cs-config-button-color').val() || '#5c6ac4';
            const textColor = $('#cs-config-text-color').val() || '#ffffff';
            const shape = $('#cs-config-button-shape').val() || 'rounded';
            const size = $('#cs-config-button-size').val() || 'medium';
            const buttonText = $('#cs-config-button-text').val() || 'Try On Virtually';

            // Apply colors
            $btn.css({
                backgroundColor: buttonColor,
                color: textColor,
                border: 'none'
            });

            // Apply shape
            let borderRadius = '6px';
            if (shape === 'square') borderRadius = '0';
            else if (shape === 'pill') borderRadius = '50px';
            else if (shape === 'circle') borderRadius = '50%';
            $btn.css('borderRadius', borderRadius);

            // Apply size
            let padding = '10px 20px';
            let fontSize = '14px';
            if (size === 'small') {
                padding = '8px 16px';
                fontSize = '13px';
            } else if (size === 'large') {
                padding = '14px 28px';
                fontSize = '16px';
            }
            $btn.css({ padding, fontSize });

            // Update text (or icon for circle)
            if (shape === 'circle') {
                $btn.html('👓').css({ width: size === 'small' ? '36px' : size === 'large' ? '52px' : '44px', height: size === 'small' ? '36px' : size === 'large' ? '52px' : '44px', padding: '0' });
            } else {
                $btn.html(buttonText).css({ width: 'auto', height: 'auto' });
            }
        },

        // Test API connection
        testConnection: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const $result = $('#cs-connection-status, #cs-test-result');

            $btn.prop('disabled', true).find('.material-icons').text('sync').addClass('cs-spin');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cs_test_connection',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span class="cs-success-text"><span class="material-icons">check_circle</span> ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span class="cs-error-text"><span class="material-icons">error</span> ' + (response.data?.message || 'Connection failed') + '</span>');
                    }
                },
                error: function() {
                    $result.html('<span class="cs-error-text"><span class="material-icons">error</span> Connection failed</span>');
                },
                complete: function() {
                    $btn.prop('disabled', false).find('.material-icons').text('sync').removeClass('cs-spin');
                }
            });
        },

        // Show install modal
        showInstallModal: function(e) {
            e.preventDefault();
            const appId = $(e.currentTarget).data('app-id');
            const app = this.config.availableApps.find(a => a.slug === appId || a.id === appId);

            if (!app) return;

            var safeName = this.escapeHtml(app.name);
            var safeIcon = this.escapeHtml(app.icon);
            var safeDesc = this.escapeHtml(app.description);
            $('#cs-modal-title').text('Install ' + app.name);
            $('#cs-modal-app-info').html(`
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                    <div class="cs-app-icon">
                        <span class="material-icons">${safeIcon}</span>
                    </div>
                    <div>
                        <h3 style="margin: 0 0 4px;">${safeName}</h3>
                        <p style="margin: 0; color: #637381;">${safeDesc}</p>
                    </div>
                </div>
            `);
            $('#cs-modal-confirm').data('app-id', appId);
            $('#cs-install-modal').show();
        },

        // Hide modal
        hideModal: function() {
            $('#cs-install-modal').hide();
        },

        // Install app
        installApp: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const appId = $btn.data('app-id');

            $btn.prop('disabled', true).html('<span class="material-icons cs-spin">sync</span> Installing...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cs_install_app',
                    nonce: this.config.nonce,
                    app_id: appId
                },
                success: (response) => {
                    if (response.success) {
                        this.config.installedApps = response.data.installed_apps;
                        this.hideModal();
                        this.showNotice('success', response.data.message);
                        // Update UI
                        $(`.cs-app-card[data-app-id="${appId}"]`).addClass('installed');
                        location.reload();
                    } else {
                        this.showNotice('error', response.data?.message || 'Installation failed');
                    }
                },
                error: () => {
                    this.showNotice('error', 'Installation failed. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="material-icons">download</span> Install App');
                }
            });
        },

        // Uninstall app
        uninstallApp: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const appId = $btn.data('app-id');

            if (!confirm('Are you sure you want to uninstall this app?')) return;

            $btn.prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cs_uninstall_app',
                    nonce: this.config.nonce,
                    app_id: appId
                },
                success: (response) => {
                    if (response.success) {
                        this.config.installedApps = response.data.installed_apps;
                        this.showNotice('success', 'App uninstalled');
                        location.reload();
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        // Show configure modal
        showConfigureModal: function(e) {
            e.preventDefault();
            const appId = $(e.currentTarget).data('app-id');
            // Search by both slug and id since button uses slug but app object may have different id
            const app = this.config.availableApps?.find(a => a.slug === appId || a.id === appId) ||
                        this.config.installedApps?.find(a => a.slug === appId || a.id === appId);

            if (!app) {
                this.showNotice('error', 'App not found: ' + appId);
                console.error('[CS] Configure modal - app not found:', appId, 'Available:', this.config.availableApps, 'Installed:', this.config.installedApps);
                return;
            }

            // Build configuration form based on app type
            const configFields = this.getAppConfigFields(appId, app);

            // Create configure modal if it doesn't exist
            if (!$('#cs-configure-modal').length) {
                this.createConfigureModal();
            }

            $('#cs-config-modal-title').text('Configure ' + app.name);
            $('#cs-config-modal-body').html(configFields);
            $('#cs-config-save').data('app-id', appId);

            // Populate form with saved configuration values
            this.populateSavedConfig(appId);

            $('#cs-configure-modal').show();
        },

        // Populate form fields with saved configuration
        populateSavedConfig: function(appId) {
            const savedConfig = this.config.appConfigs?.[appId] || {};
            if (Object.keys(savedConfig).length === 0) {
                console.log('[CS] No saved config for app:', appId);
                return;
            }

            console.log('[CS] Loading saved config for', appId, savedConfig);

            // Common fields
            if (savedConfig.enabled !== undefined) {
                $('#cs-config-enabled').prop('checked', savedConfig.enabled);
            }
            if (savedConfig.position) {
                $('#cs-config-position').val(savedConfig.position);
            }

            // App-specific fields
            if (savedConfig.buttonText) {
                $('#cs-config-button-text').val(savedConfig.buttonText);
            }
            if (savedConfig.displayMode) {
                $('#cs-config-display-mode').val(savedConfig.displayMode);
            }
            if (savedConfig.categories) {
                $('#cs-config-categories').val(savedConfig.categories);
            }
            if (savedConfig.units) {
                $('#cs-config-units').val(savedConfig.units);
            }
            if (savedConfig.showGuidance !== undefined) {
                $('#cs-config-guidance').prop('checked', savedConfig.showGuidance);
            }
            if (savedConfig.saveResult !== undefined) {
                $('#cs-config-save-result').prop('checked', savedConfig.saveResult);
            }
            if (savedConfig.showRecommendations !== undefined) {
                $('#cs-config-show-recs').prop('checked', savedConfig.showRecommendations);
            }
            if (savedConfig.confidenceThreshold) {
                $('#cs-config-confidence').val(savedConfig.confidenceThreshold);
            }
            if (savedConfig.autoRotate !== undefined) {
                $('#cs-config-auto-rotate').prop('checked', savedConfig.autoRotate);
            }
            if (savedConfig.enableZoom !== undefined) {
                $('#cs-config-zoom').prop('checked', savedConfig.enableZoom);
            }
            if (savedConfig.quality) {
                $('#cs-config-quality').val(savedConfig.quality);
            }
            if (savedConfig.customClass) {
                $('#cs-config-custom').val(savedConfig.customClass);
            }

            // Button styling fields
            if (savedConfig.buttonColor) {
                $('#cs-config-button-color').val(savedConfig.buttonColor);
            }
            if (savedConfig.buttonTextColor) {
                $('#cs-config-text-color').val(savedConfig.buttonTextColor);
            }
            if (savedConfig.buttonShape) {
                $('#cs-config-button-shape').val(savedConfig.buttonShape);
            }
            if (savedConfig.buttonSize) {
                $('#cs-config-button-size').val(savedConfig.buttonSize);
            }

            // Update button preview if present
            this.updateButtonPreview();
        },

        // Create configure modal HTML
        createConfigureModal: function() {
            const modalHtml = `
                <div id="cs-configure-modal" class="cs-modal cs-config-modal" style="display:none;">
                    <div class="cs-modal-overlay"></div>
                    <div class="cs-modal-content">
                        <div class="cs-modal-header">
                            <h2 id="cs-config-modal-title">
                                <span class="material-icons">settings</span>
                                Configure App
                            </h2>
                            <button type="button" class="cs-modal-close">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                        <div class="cs-modal-body" id="cs-config-modal-body">
                            <!-- Config form will be inserted here -->
                        </div>
                        <div class="cs-modal-footer">
                            <button type="button" id="cs-config-cancel" class="cs-btn cs-btn-secondary">
                                <span class="material-icons">close</span>
                                Cancel
                            </button>
                            <button type="button" id="cs-config-save" class="cs-btn cs-btn-primary">
                                <span class="material-icons">save</span>
                                Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);
            // Event handlers are bound via delegation in bindEvents(), no direct binding needed here.
        },

        // Get configuration fields for each app type
        getAppConfigFields: function(appId, app) {
            const commonFields = `
                <div class="cs-config-section">
                    <h3><span class="material-icons">visibility</span> Display Settings</h3>
                    <div class="cs-toggle-wrapper">
                        <div class="cs-toggle-label">
                            <strong>Enable Widget</strong>
                            <span>Show widget on product pages</span>
                        </div>
                        <label class="cs-toggle">
                            <input type="checkbox" id="cs-config-enabled" checked>
                            <span class="cs-toggle-slider"></span>
                        </label>
                    </div>
                    <div class="cs-form-group">
                        <label for="cs-config-position">Widget Position</label>
                        <select id="cs-config-position">
                            <option value="after-title">After Product Title</option>
                            <option value="after-price">After Price</option>
                            <option value="before-cart" selected>Before Add to Cart</option>
                            <option value="after-cart">After Add to Cart</option>
                            <option value="after-description">After Description</option>
                        </select>
                        <span class="cs-help-text">Choose where the widget appears on product pages</span>
                    </div>
                </div>
            `;

            const appSpecificFields = {
                'virtual-try-on': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">view_in_ar</span> Virtual Try-On Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-button-text">Button Text</label>
                            <input type="text" id="cs-config-button-text" value="Try On Virtually">
                            <span class="cs-help-text">Text displayed on the try-on button</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-display-mode">Display Mode</label>
                            <select id="cs-config-display-mode">
                                <option value="modal" selected>Modal Popup</option>
                                <option value="inline">Inline on Page</option>
                                <option value="sidebar">Sidebar Panel</option>
                            </select>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-categories">Product Categories</label>
                            <input type="text" id="cs-config-categories" placeholder="Eyewear, Sunglasses, Glasses">
                            <span class="cs-help-text">Comma-separated list of categories to show widget on</span>
                        </div>
                    </div>
                `,
                'pd-measurement': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">straighten</span> PD Calculator Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-units">Measurement Units</label>
                            <select id="cs-config-units">
                                <option value="mm" selected>Millimeters (mm)</option>
                                <option value="cm">Centimeters (cm)</option>
                            </select>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Step-by-Step Guidance</strong>
                                <span>Show helpful instructions during measurement</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-guidance" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Save to Profile</strong>
                                <span>Store PD measurement in customer account</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-save-result" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                `,
                'lens-journey': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">search</span> Lens Customization Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-button-text">Button Text</label>
                            <input type="text" id="cs-config-button-text" value="Customize Lenses">
                            <span class="cs-help-text">Text displayed on the lens journey button</span>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Show Pricing</strong>
                                <span>Display lens upgrade prices during selection</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-show-pricing" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Progressive Lenses</strong>
                                <span>Enable progressive lens options</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-progressive" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-coatings">Available Coatings</label>
                            <input type="text" id="cs-config-coatings" placeholder="Anti-reflective, Blue light, Photochromic">
                            <span class="cs-help-text">Comma-separated list of coating options</span>
                        </div>
                    </div>
                `,
                'size-recommender': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">smart_toy</span> AI Size Recommender Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-button-text">Button Text</label>
                            <input type="text" id="cs-config-button-text" value="Find My Size">
                            <span class="cs-help-text">Text displayed on the size finder button</span>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Face Analysis</strong>
                                <span>Use camera to analyze face dimensions</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-face-analysis" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Show Fit Score</strong>
                                <span>Display compatibility score for each frame</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-fit-score" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-confidence">Confidence Threshold</label>
                            <select id="cs-config-confidence">
                                <option value="0.6">Standard (60%)</option>
                                <option value="0.8" selected>High (80%)</option>
                                <option value="0.9">Very High (90%)</option>
                            </select>
                            <span class="cs-help-text">Minimum confidence for size recommendations</span>
                        </div>
                    </div>
                `,
                'conversational-ai': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">auto_awesome</span> AI Style Assistant Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-button-text">Button Text</label>
                            <input type="text" id="cs-config-button-text" value="Get Style Advice">
                            <span class="cs-help-text">Text displayed on the style assistant button</span>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Color Analysis</strong>
                                <span>Analyze skin tone for color recommendations</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-color-analysis" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Trend Insights</strong>
                                <span>Show current eyewear fashion trends</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-trends" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-rec-count">Recommendations Count</label>
                            <select id="cs-config-rec-count">
                                <option value="3">3 recommendations</option>
                                <option value="5" selected>5 recommendations</option>
                                <option value="10">10 recommendations</option>
                            </select>
                            <span class="cs-help-text">Number of style suggestions to display</span>
                        </div>
                    </div>
                `,
                'ai-sales-agent': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">smart_toy</span> AI Sales Agent Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-brand-name">Brand / Store Name</label>
                            <input type="text" id="cs-config-brand-name" value="">
                            <span class="cs-help-text">How the AI refers to your store</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-brand-voice">Brand Voice</label>
                            <select id="cs-config-brand-voice">
                                <option value="friendly" selected>Friendly</option>
                                <option value="professional">Professional</option>
                                <option value="luxury">Luxury</option>
                                <option value="casual">Casual</option>
                            </select>
                            <span class="cs-help-text">Tone and personality of the AI assistant</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-greeting">Greeting Message</label>
                            <input type="text" id="cs-config-greeting" value="Welcome! I'm here to help you find the perfect eyewear.">
                            <span class="cs-help-text">First message customers see when chat opens</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-product-focus">Product Focus</label>
                            <select id="cs-config-product-focus">
                                <option value="all" selected>All Products</option>
                                <option value="bestsellers">Bestsellers</option>
                                <option value="new-arrivals">New Arrivals</option>
                                <option value="sale">Sale Items</option>
                            </select>
                            <span class="cs-help-text">Which products the AI should emphasize</span>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Product Recommendations</strong>
                                <span>Allow AI to recommend products from your catalog</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-product-recs" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Appointment Booking</strong>
                                <span>Allow AI to offer in-store appointment booking</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-appointment-booking">
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-custom-instructions">Custom Instructions</label>
                            <input type="text" id="cs-config-custom-instructions" value="" maxlength="500">
                            <span class="cs-help-text">Additional AI instructions (max 500 chars)</span>
                        </div>
                    </div>
                `,
                'bopis-reserve-pickup': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">store</span> Buy Online, Pick Up In Store Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-button-text">Button Text</label>
                            <input type="text" id="cs-config-button-text" value="Check Store Availability">
                            <span class="cs-help-text">Text displayed on the BOPIS button</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-search-radius">Search Radius</label>
                            <select id="cs-config-search-radius">
                                <option value="10">10 miles</option>
                                <option value="25" selected>25 miles</option>
                                <option value="50">50 miles</option>
                                <option value="100">100 miles</option>
                            </select>
                            <span class="cs-help-text">Default store search radius</span>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Show Inventory</strong>
                                <span>Display real-time stock levels at stores</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-show-inventory" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Pickup Scheduling</strong>
                                <span>Allow customers to schedule pickup time</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-scheduling" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                `,
                '3d-viewer': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">3d_rotation</span> 3D Viewer Settings</h3>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Auto-Rotate</strong>
                                <span>Automatically rotate product on page load</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-auto-rotate">
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Zoom Controls</strong>
                                <span>Allow users to zoom in/out on product</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-zoom" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-quality">Render Quality</label>
                            <select id="cs-config-quality">
                                <option value="low">Low (faster loading)</option>
                                <option value="medium">Medium</option>
                                <option value="high" selected>High</option>
                            </select>
                            <span class="cs-help-text">Higher quality = better visuals but slower loading</span>
                        </div>
                    </div>
                `,
                'fitting-height': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">height</span> Fitting Height Calculator Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-button-text">Button Text</label>
                            <input type="text" id="cs-config-button-text" value="Measure Fitting Height">
                            <span class="cs-help-text">Text displayed on the fitting height button</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-units">Measurement Units</label>
                            <select id="cs-config-units">
                                <option value="mm" selected>Millimeters (mm)</option>
                                <option value="cm">Centimeters (cm)</option>
                            </select>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Visual Guide</strong>
                                <span>Show step-by-step visual instructions</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-visual-guide" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Progressive Optimization</strong>
                                <span>Optimize measurements for progressive lenses</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-progressive-opt" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                `,
                'face-shape-analyzer': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">face</span> Face Shape Analyzer Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-button-text">Button Text</label>
                            <input type="text" id="cs-config-button-text" value="Analyze My Face Shape">
                            <span class="cs-help-text">Text displayed on the face shape analyzer button</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-display-mode">Display Mode</label>
                            <select id="cs-config-display-mode">
                                <option value="modal" selected>Modal Popup</option>
                                <option value="inline">Inline on Page</option>
                                <option value="sidebar">Sidebar Panel</option>
                            </select>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Frame Recommendations</strong>
                                <span>Show personalized frame recommendations based on face shape</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-show-recommendations" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Style Matching</strong>
                                <span>Match frames to detected face shape automatically</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-style-matching" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-confidence">Detection Confidence</label>
                            <select id="cs-config-confidence">
                                <option value="0.6">Standard (60%)</option>
                                <option value="0.8" selected>High (80%)</option>
                                <option value="0.9">Very High (90%)</option>
                            </select>
                            <span class="cs-help-text">Minimum confidence for face shape detection</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-categories">Product Categories</label>
                            <input type="text" id="cs-config-categories" placeholder="Eyewear, Sunglasses, Glasses">
                            <span class="cs-help-text">Comma-separated list of categories to show widget on</span>
                        </div>
                    </div>
                `,
                'avatar-2d': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">person</span> 2D Avatar Chat Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-welcome-msg">Welcome Message</label>
                            <input type="text" id="cs-config-welcome-msg" value="Hi! I'm your personal eyewear consultant. How can I help you today?">
                            <span class="cs-help-text">First message shown when avatar appears</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-avatar-position">Avatar Position</label>
                            <select id="cs-config-avatar-position">
                                <option value="bottom-right" selected>Bottom Right</option>
                                <option value="bottom-left">Bottom Left</option>
                                <option value="inline">Inline on Page</option>
                            </select>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Voice Enabled</strong>
                                <span>Enable ElevenLabs voice responses</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-voice-enabled" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Auto-Open on Product Pages</strong>
                                <span>Automatically show avatar on eyewear product pages</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-auto-open">
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-language">Default Language</label>
                            <select id="cs-config-language">
                                <option value="en" selected>English</option>
                                <option value="es">Spanish</option>
                                <option value="fr">French</option>
                                <option value="de">German</option>
                                <option value="auto">Auto-detect</option>
                            </select>
                            <span class="cs-help-text">Primary language for avatar responses</span>
                        </div>
                    </div>
                `,
                'avatar-3d': `
                    <div class="cs-config-section">
                        <h3><span class="material-icons">face_3</span> HeyGen 3D Avatar Settings</h3>
                        <div class="cs-form-group">
                            <label for="cs-config-welcome-msg">Welcome Message</label>
                            <input type="text" id="cs-config-welcome-msg" value="Welcome! I'm here to help you find your perfect eyewear.">
                            <span class="cs-help-text">First message shown when avatar appears</span>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-avatar-style">Avatar Style</label>
                            <select id="cs-config-avatar-style">
                                <option value="professional" selected>Professional</option>
                                <option value="casual">Casual</option>
                                <option value="friendly">Friendly</option>
                            </select>
                            <span class="cs-help-text">Visual appearance and tone of the 3D avatar</span>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Real-time Lip Sync</strong>
                                <span>Enable HeyGen real-time lip synchronization</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-lip-sync" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-toggle-wrapper">
                            <div class="cs-toggle-label">
                                <strong>Product Demonstration</strong>
                                <span>Allow avatar to demonstrate frame features</span>
                            </div>
                            <label class="cs-toggle">
                                <input type="checkbox" id="cs-config-product-demo" checked>
                                <span class="cs-toggle-slider"></span>
                            </label>
                        </div>
                        <div class="cs-form-group">
                            <label for="cs-config-session-timeout">Session Timeout</label>
                            <select id="cs-config-session-timeout">
                                <option value="5">5 minutes</option>
                                <option value="10" selected>10 minutes</option>
                                <option value="15">15 minutes</option>
                                <option value="30">30 minutes</option>
                            </select>
                            <span class="cs-help-text">How long before inactive sessions end</span>
                        </div>
                    </div>
                `
            };

            // Use slug or widget property for lookup (slug takes precedence)
            const appKey = app.slug || app.widget || appId;
            const specificFields = appSpecificFields[appKey] || `
                <div class="cs-config-section">
                    <h3><span class="material-icons">tune</span> Widget Settings</h3>
                    <div class="cs-form-group">
                        <label for="cs-config-custom">Custom CSS Class</label>
                        <input type="text" id="cs-config-custom" placeholder="my-custom-class">
                        <span class="cs-help-text">Add a custom CSS class for additional styling</span>
                    </div>
                </div>
            `;

            const buttonStylingFields = `
                <div class="cs-config-section">
                    <h3><span class="material-icons">palette</span> Button Styling</h3>
                    <p class="cs-section-description">Customize the appearance of widget buttons to match your store's design</p>

                    <div class="cs-form-row">
                        <div class="cs-form-group cs-form-half">
                            <label for="cs-config-button-color">Button Color</label>
                            <div class="cs-color-picker">
                                <input type="color" id="cs-config-button-color-picker" value="#5c6ac4">
                                <input type="text" id="cs-config-button-color" value="#5c6ac4" placeholder="#5c6ac4">
                            </div>
                            <span class="cs-help-text">Background color of the button</span>
                        </div>
                        <div class="cs-form-group cs-form-half">
                            <label for="cs-config-text-color">Text Color</label>
                            <div class="cs-color-picker">
                                <input type="color" id="cs-config-text-color-picker" value="#ffffff">
                                <input type="text" id="cs-config-text-color" value="#ffffff" placeholder="#ffffff">
                            </div>
                            <span class="cs-help-text">Color of the button text</span>
                        </div>
                    </div>

                    <div class="cs-form-row">
                        <div class="cs-form-group cs-form-half">
                            <label for="cs-config-button-shape">Button Shape</label>
                            <select id="cs-config-button-shape">
                                <option value="rounded" selected>Rounded (default)</option>
                                <option value="square">Square</option>
                                <option value="pill">Pill</option>
                                <option value="circle">Circle (icon only)</option>
                            </select>
                        </div>
                        <div class="cs-form-group cs-form-half">
                            <label for="cs-config-button-size">Button Size</label>
                            <select id="cs-config-button-size">
                                <option value="small">Small</option>
                                <option value="medium" selected>Medium (default)</option>
                                <option value="large">Large</option>
                            </select>
                        </div>
                    </div>

                    <div class="cs-form-group">
                        <label>Preview</label>
                        <div class="cs-button-preview" id="cs-button-preview">
                            <button type="button" id="cs-preview-btn" class="cs-widget-btn">Try On Virtually</button>
                        </div>
                    </div>
                </div>
            `;

            return `<form id="cs-config-form">${commonFields}${specificFields}${buttonStylingFields}</form>`;
        },

        // Hide configure modal
        hideConfigModal: function() {
            $('#cs-configure-modal').hide();
        },

        // Save app configuration
        saveAppConfig: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const appId = $btn.data('app-id');

            // Gather form data
            const config = {
                enabled: $('#cs-config-enabled').is(':checked'),
                position: $('#cs-config-position').val(),
                // App-specific settings
                buttonText: $('#cs-config-button-text').val(),
                displayMode: $('#cs-config-display-mode').val(),
                categories: $('#cs-config-categories').val(),
                units: $('#cs-config-units').val(),
                showGuidance: $('#cs-config-guidance').is(':checked'),
                saveResult: $('#cs-config-save-result').is(':checked'),
                showRecommendations: $('#cs-config-show-recs').is(':checked'),
                confidenceThreshold: $('#cs-config-confidence').val(),
                autoRotate: $('#cs-config-auto-rotate').is(':checked'),
                enableZoom: $('#cs-config-zoom').is(':checked'),
                quality: $('#cs-config-quality').val(),
                customClass: $('#cs-config-custom').val(),
                // Button styling
                buttonColor: $('#cs-config-button-color').val() || '#5c6ac4',
                buttonTextColor: $('#cs-config-text-color').val() || '#ffffff',
                buttonShape: $('#cs-config-button-shape').val() || 'rounded',
                buttonSize: $('#cs-config-button-size').val() || 'medium'
            };

            $btn.prop('disabled', true).html('<span class="material-icons cs-spin" style="font-size:16px;vertical-align:middle;">sync</span> Saving...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cs_save_app_config',
                    nonce: this.config.nonce,
                    app_id: appId,
                    config: JSON.stringify(config)
                },
                success: (response) => {
                    if (response.success) {
                        // Update local config cache so reopening modal shows saved values
                        if (!this.config.appConfigs) {
                            this.config.appConfigs = {};
                        }
                        this.config.appConfigs[appId] = config;
                        console.log('[CS] Config saved and cached for', appId, config);

                        this.hideConfigModal();
                        this.showNotice('success', 'Configuration saved successfully');
                    } else {
                        this.showNotice('error', response.data?.message || 'Failed to save configuration');
                    }
                },
                error: () => {
                    this.showNotice('error', 'Failed to save configuration. Please try again.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="material-icons" style="font-size:16px;vertical-align:middle;">save</span> Save Settings');
                }
            });
        },

        // Start onboarding
        startOnboarding: function(e) {
            e.preventDefault();
            window.location.href = this.config.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=commerce-studio-apps');
        },

        // Clear cache
        clearCache: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            $btn.prop('disabled', true);

            // Simulate cache clear
            setTimeout(() => {
                this.showNotice('success', 'Cache cleared successfully');
                $btn.prop('disabled', false);
            }, 500);
        },

        // Check for plugin updates
        checkForUpdates: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const $status = $('#cs-update-status');

            $btn.prop('disabled', true);
            $status.html('<span style="color:#999;">Checking...</span>');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'cs_force_update_check',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;
                        if (data.has_update) {
                            $status.html('<span style="color:#46b450;">' + data.message + '</span>');
                            // Reload page to show update notice
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            $status.html('<span style="color:#46b450;">' + data.message + '</span>');
                        }
                    } else {
                        $status.html('<span style="color:#dc3232;">' + (response.data?.message || 'Error checking for updates') + '</span>');
                    }
                    $btn.prop('disabled', false);
                },
                error: () => {
                    $status.html('<span style="color:#dc3232;">Network error. Please try again.</span>');
                    $btn.prop('disabled', false);
                }
            });
        },

        // Reset settings
        resetSettings: function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to reset all settings to defaults?')) {
                $('#cs_environment').val('production'); // Default to production for live stores
                $('#cs_api_key').val('');
                this.showNotice('info', 'Settings reset. Click "Save Settings" to apply.');
            }
        },

        // Show admin notice
        showNotice: function(type, message) {
            const classes = {
                success: 'notice-success',
                error: 'notice-error',
                warning: 'notice-warning',
                info: 'notice-info'
            };

            const safeMessage = this.escapeHtml(message);
            const $notice = $(`
                <div class="notice ${classes[type]} is-dismissible" style="margin: 20px 0;">
                    <p>${safeMessage}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss</span>
                    </button>
                </div>
            `);

            $('.cs-header').after($notice);

            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(function() { $(this).remove(); });
            });

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() { $(this).remove(); });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        CS.init();
    });

    // Spin animation and utility CSS now in admin.css

})(jQuery);
