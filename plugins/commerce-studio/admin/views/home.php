<?php
/**
 * Commerce Studio - Home/Dashboard Page
 * Matches Shopify app HomePage.tsx - Only shows onboarding until complete
 */

if (!defined('WPINC')) {
    die;
}

$is_onboarded = get_option('cs_onboarding_complete', false);
$installed_apps = CS_Apps::get_installed_apps();
$available_apps = CS_Apps::get_available_apps();
$environment = CS_Config::get_environment();
$last_sync = get_option('cs_last_product_sync', '');
$products_synced = get_option('cs_products_synced_count', 0);
$total_products = function_exists('wc_get_products') ? count(wc_get_products(['status' => 'publish', 'limit' => -1, 'return' => 'ids'])) : 0;
$last_oc_sync = get_option('cs_last_orders_customers_sync', '');
$orders_synced = get_option('cs_orders_synced_count', 0);
$customers_synced = get_option('cs_customers_synced_count', 0);

// If not onboarded, redirect directly to onboarding page
if (!$is_onboarded) {
    wp_redirect(admin_url('admin.php?page=commerce-studio-onboarding'));
    exit;
}
?>

<!-- Load Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<div class="wrap cs-admin">
    <!-- ============================================ -->
    <!-- ONBOARDED STATE - Show Full Dashboard       -->
    <!-- ============================================ -->
    <div class="cs-header">
        <div class="cs-header-content">
            <h1>
                <span class="material-icons">visibility</span>
                Commerce Studio
            </h1>
            <p class="cs-subtitle">AI-powered eyewear retail solutions for your WooCommerce store</p>
        </div>
        <div class="cs-header-actions">
            <a href="<?php echo admin_url('admin.php?page=commerce-studio-apps'); ?>" class="button button-primary button-hero">
                <span class="material-icons">apps</span>
                Explore Apps
            </a>
        </div>
    </div>

    <!-- Connected Banner -->
    <div class="cs-success-banner">
        <span class="material-icons">check_circle</span>
        <div>
            <strong>Successfully connected!</strong>
            <p>Your store is connected to Commerce Studio (<?php echo esc_html(ucfirst($environment)); ?> environment). You can start using our AI-powered eyewear tools.</p>
        </div>
    </div>

    <div class="cs-dashboard-grid">
        <!-- ROW 1: Product Sync Card -->
        <div class="cs-card cs-card-full-width">
            <div class="cs-card-header">
                <div class="cs-step-badge">Step 1</div>
                <h2>
                    <span class="material-icons">sync</span>
                    Sync Product Catalog
                </h2>
            </div>
            <div class="cs-card-body">
                <p>Sync your WooCommerce products with Commerce Studio to enable Virtual Try-On and other features.</p>

                <div class="cs-sync-stats">
                    <div class="cs-sync-stat">
                        <span class="cs-sync-number"><?php echo esc_html($total_products); ?></span>
                        <span class="cs-sync-label">WooCommerce Products</span>
                    </div>
                    <div class="cs-sync-stat">
                        <span class="cs-sync-number"><?php echo esc_html($products_synced); ?></span>
                        <span class="cs-sync-label">Synced to Commerce Studio</span>
                    </div>
                </div>

                <?php if ($last_sync): ?>
                <p class="cs-sync-info">
                    <span class="material-icons">schedule</span>
                    Last synced: <strong><?php echo esc_html(human_time_diff(strtotime($last_sync), current_time('timestamp'))); ?> ago</strong>
                    <br><small>(<?php echo esc_html(date('M j, Y g:i A', strtotime($last_sync))); ?>)</small>
                </p>
                <?php else: ?>
                <p class="cs-sync-info cs-sync-warning">
                    <span class="material-icons">warning</span>
                    Products have not been synced yet. Click below to sync your catalog.
                </p>
                <?php endif; ?>

                <?php $next_product_sync = wp_next_scheduled('cs_scheduled_product_sync'); ?>
                <?php if ($next_product_sync): ?>
                <p class="cs-sync-info" style="background: #f0fdf4; color: #166534;">
                    <span class="material-icons">update</span>
                    Next automatic sync: <strong><?php echo esc_html(human_time_diff(current_time('timestamp'), $next_product_sync)); ?></strong>
                    <br><small>(<?php echo esc_html(date('M j, Y g:i A', $next_product_sync)); ?>)</small>
                </p>
                <?php endif; ?>

                <div class="cs-card-actions">
                    <button type="button" class="button button-primary button-hero" id="cs-sync-products-btn">
                        <span class="material-icons">sync</span>
                        <span class="cs-btn-text">Sync Products Now</span>
                    </button>
                </div>

                <div id="cs-sync-progress" style="display: none;">
                    <div class="cs-progress-bar">
                        <div class="cs-progress-fill"></div>
                    </div>
                    <p class="cs-sync-status">Syncing products...</p>
                </div>

                <div id="cs-sync-result" style="display: none;"></div>
            </div>
        </div>

        <!-- ROW 2: Orders & Customers Sync Card -->
        <div class="cs-card cs-card-full-width">
            <div class="cs-card-header">
                <div class="cs-step-badge">Step 2</div>
                <h2>
                    <span class="material-icons">shopping_cart</span>
                    Sync Orders &amp; Customers
                </h2>
            </div>
            <div class="cs-card-body">
                <p>Sync your WooCommerce orders and customers with Commerce Studio for analytics and ML calibration.</p>

                <div class="cs-sync-privacy-info" style="background: #f0f7ff; border: 1px solid #c8dff7; border-radius: 6px; padding: 14px 16px; margin-bottom: 16px;">
                    <p style="margin: 0 0 8px; font-weight: 600; color: #1a3a5c;">
                        <span class="material-icons" style="font-size: 16px; vertical-align: text-bottom;">verified_user</span>
                        What gets synced (privacy-safe)
                    </p>
                    <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                        <div>
                            <p style="margin: 0 0 4px; font-weight: 500; color: #333;">Synced:</p>
                            <ul style="margin: 0; padding-left: 18px; color: #555; font-size: 13px;">
                                <li>Order totals, currency, status</li>
                                <li>Product IDs and quantities</li>
                                <li>Country/region (billing &amp; shipping)</li>
                                <li>Customer spend totals and order counts</li>
                            </ul>
                        </div>
                        <div>
                            <p style="margin: 0 0 4px; font-weight: 500; color: #333;">Never synced:</p>
                            <ul style="margin: 0; padding-left: 18px; color: #555; font-size: 13px;">
                                <li>Names, emails, phone numbers</li>
                                <li>Street addresses or ZIP codes</li>
                                <li>Payment details or IP addresses</li>
                                <li>Any personally identifiable info (PII)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="cs-sync-stats">
                    <div class="cs-sync-stat">
                        <span class="cs-sync-number"><?php echo esc_html($orders_synced); ?></span>
                        <span class="cs-sync-label">Orders Synced</span>
                    </div>
                    <div class="cs-sync-stat">
                        <span class="cs-sync-number"><?php echo esc_html($customers_synced); ?></span>
                        <span class="cs-sync-label">Customers Synced</span>
                    </div>
                </div>

                <?php if ($last_oc_sync): ?>
                <p class="cs-sync-info">
                    <span class="material-icons">schedule</span>
                    Last synced: <strong><?php echo esc_html(human_time_diff(strtotime($last_oc_sync), current_time('timestamp'))); ?> ago</strong>
                    <br><small>(<?php echo esc_html(date('M j, Y g:i A', strtotime($last_oc_sync))); ?>)</small>
                </p>
                <?php else: ?>
                <p class="cs-sync-info cs-sync-warning">
                    <span class="material-icons">warning</span>
                    Orders and customers have not been synced yet. Click below to sync.
                </p>
                <?php endif; ?>

                <?php $next_orders_sync = wp_next_scheduled('cs_scheduled_orders_sync'); ?>
                <?php if ($next_orders_sync): ?>
                <p class="cs-sync-info" style="background: #f0fdf4; color: #166534;">
                    <span class="material-icons">update</span>
                    Next automatic sync: <strong><?php echo esc_html(human_time_diff(current_time('timestamp'), $next_orders_sync)); ?></strong>
                    <br><small>(<?php echo esc_html(date('M j, Y g:i A', $next_orders_sync)); ?>)</small>
                </p>
                <?php endif; ?>

                <div class="cs-card-actions">
                    <button type="button" class="button button-primary button-hero" id="cs-sync-orders-btn">
                        <span class="material-icons">shopping_cart</span>
                        <span class="cs-btn-text">Sync Orders &amp; Customers</span>
                    </button>
                </div>

                <div id="cs-sync-orders-progress" style="display: none;">
                    <div class="cs-progress-bar">
                        <div class="cs-progress-fill"></div>
                    </div>
                    <p class="cs-sync-status">Syncing orders &amp; customers...</p>
                </div>

                <div id="cs-sync-orders-result" style="display: none;"></div>
            </div>
        </div>

        <!-- ROW 3: Go to Dashboard Card -->
        <div class="cs-card cs-card-full-width">
            <div class="cs-card-header">
                <div class="cs-step-badge">Step 3</div>
                <h2>
                    <span class="material-icons">dashboard</span>
                    Check Stats on Commerce Studio
                </h2>
            </div>
            <div class="cs-card-body">
                <p>View detailed analytics, manage your apps, and monitor performance on the Commerce Studio dashboard.</p>

                <div class="cs-dashboard-benefits">
                    <div class="cs-benefit-item">
                        <span class="material-icons">analytics</span>
                        <span>View real-time analytics and conversion data</span>
                    </div>
                    <div class="cs-benefit-item">
                        <span class="material-icons">tune</span>
                        <span>Configure advanced widget settings</span>
                    </div>
                    <div class="cs-benefit-item">
                        <span class="material-icons">support</span>
                        <span>Access support and documentation</span>
                    </div>
                </div>

                <div class="cs-card-actions">
                    <a href="<?php echo esc_url(CS_Config::get_dashboard_url()); ?>" target="_blank" class="button button-primary button-hero">
                        <span class="material-icons">open_in_new</span>
                        Go to My Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- ROW 4: Available Apps Card -->
        <div class="cs-card">
            <div class="cs-card-header">
                <div class="cs-step-badge">Step 4</div>
                <h2>
                    <span class="material-icons">apps</span>
                    Install Apps
                </h2>
            </div>
            <div class="cs-card-body">
                <p>Commerce Studio offers <?php echo count($available_apps); ?> specialized apps for eyewear retail:</p>

                <div class="cs-app-categories">
                    <?php
                    $categories = [];
                    foreach ($available_apps as $app) {
                        $cat = $app['category'];
                        $categories[$cat] = ($categories[$cat] ?? 0) + 1;
                    }
                    foreach ($categories as $category => $count):
                    ?>
                    <div class="cs-category-row">
                        <span class="cs-category-name"><?php echo esc_html(ucwords(str_replace('-', ' ', $category))); ?></span>
                        <span class="cs-category-badge"><?php echo $count; ?> app<?php echo $count > 1 ? 's' : ''; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="cs-card-actions">
                    <a href="<?php echo admin_url('admin.php?page=commerce-studio-apps'); ?>" class="button">
                        Explore All Apps
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured App -->
    <div class="cs-featured-card">
        <div class="cs-featured-icon">
            <span class="material-icons">visibility</span>
        </div>
        <div class="cs-featured-content">
            <h2>Featured: Eyewear Virtual Try-On</h2>
            <h3>Transform Your Eyewear Store</h3>
            <p>
                Our Virtual Try-On technology uses advanced AR to let customers try on frames virtually,
                leading to higher conversion rates and fewer returns. Perfect for sunglasses, eyeglasses,
                and specialty eyewear.
            </p>
            <div class="cs-feature-badges">
                <span class="cs-feature-badge">
                    <span class="material-icons">check_circle</span>
                    Face shape analysis
                </span>
                <span class="cs-feature-badge">
                    <span class="material-icons">check_circle</span>
                    AR-powered fitting
                </span>
                <span class="cs-feature-badge">
                    <span class="material-icons">check_circle</span>
                    Size recommendations
                </span>
            </div>
        </div>
        <div class="cs-featured-action">
            <a href="<?php echo admin_url('admin.php?page=commerce-studio-apps'); ?>" class="button button-primary button-hero">
                Install Virtual Try-On
            </a>
        </div>
    </div>

    <!-- Status Footer -->
    <div class="cs-status-footer">
        <div class="cs-status-item">
            <span class="material-icons">cloud</span>
            <span>Environment: <strong><?php echo esc_html(ucfirst($environment)); ?></strong></span>
        </div>
        <div class="cs-status-item">
            <span class="material-icons">apps</span>
            <span>Apps Installed: <strong><?php echo count($installed_apps); ?></strong></span>
        </div>
        <div class="cs-status-item">
            <button type="button" class="button button-small" id="cs-test-connection">
                <span class="material-icons">sync</span>
                Test Connection
            </button>
            <span id="cs-connection-status"></span>
        </div>
    </div>
</div>

<style>
/* Onboarding Step Badge */
.cs-step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0071e3, #00c7be);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 12px;
    margin-right: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Full-width cards for onboarding steps */
.cs-card-full-width {
    grid-column: 1 / -1;
}

.cs-card-full-width .cs-card-header {
    display: flex;
    align-items: center;
}

.cs-card-full-width .cs-card-header h2 {
    margin: 0;
}

/* Dashboard benefits list */
.cs-dashboard-benefits {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 16px 0 24px;
}

.cs-benefit-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 14px;
    color: #1d1d1f;
}

.cs-benefit-item .material-icons {
    font-size: 20px;
    color: #0071e3;
}

/* Hero button adjustments */
.button-hero {
    display: inline-flex !important;
    align-items: center;
    gap: 8px;
}

.button-hero .material-icons {
    font-size: 20px;
}

/* Product Sync Card Styles */
.cs-sync-stats {
    display: flex;
    gap: 24px;
    margin: 16px 0;
}

.cs-sync-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px 24px;
    background: #f8f9fa;
    border-radius: 8px;
    flex: 1;
}

.cs-sync-number {
    font-size: 32px;
    font-weight: 700;
    color: #1d1d1f;
    line-height: 1;
}

.cs-sync-label {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
    text-align: center;
}

.cs-sync-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #f0f9ff;
    border-radius: 8px;
    margin: 16px 0;
    color: #0369a1;
    font-size: 14px;
}

.cs-sync-info .material-icons {
    font-size: 20px;
}

.cs-sync-info.cs-sync-warning {
    background: #fef3c7;
    color: #92400e;
}

#cs-sync-products-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

#cs-sync-products-btn .material-icons {
    font-size: 18px;
}

#cs-sync-products-btn.syncing .material-icons {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.cs-progress-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin: 16px 0;
}

.cs-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0071e3, #00c7be);
    width: 0%;
    transition: width 0.3s ease;
    animation: progress-pulse 1.5s ease-in-out infinite;
}

@keyframes progress-pulse {
    0%, 100% { width: 30%; margin-left: 0; }
    50% { width: 60%; margin-left: 20%; }
}

.cs-sync-status {
    text-align: center;
    color: #6b7280;
    font-size: 14px;
}

#cs-sync-result {
    padding: 12px 16px;
    border-radius: 8px;
    margin-top: 16px;
}

#cs-sync-result.success {
    background: #d1fae5;
    color: #065f46;
}

#cs-sync-result.error {
    background: #fee2e2;
    color: #991b1b;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Defensive check for commerceStudio configuration
    if (typeof window.commerceStudio === 'undefined') {
        console.error('Commerce Studio: Configuration not loaded. Please refresh the page.');
        $('#cs-sync-result')
            .addClass('error')
            .html('<span class="material-icons" style="vertical-align: middle;">error</span> Configuration error. Please refresh the page.')
            .show();
        return;
    }

    var config = window.commerceStudio;

    // Product sync button handler
    $('#cs-sync-products-btn').on('click', function() {
        var $btn = $(this);
        var $progress = $('#cs-sync-progress');
        var $result = $('#cs-sync-result');

        // Validate nonce exists
        if (!config.nonce) {
            $result
                .addClass('error')
                .html('<span class="material-icons" style="vertical-align: middle;">error</span> Session expired. Please refresh the page and try again.')
                .show();
            return;
        }

        // Disable button and show loading state
        $btn.prop('disabled', true).addClass('syncing');
        $btn.find('.cs-btn-text').text('Syncing...');
        $progress.show();
        $result.hide();

        // Call sync AJAX
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cs_sync_products',
                nonce: config.nonce
            },
            success: function(response) {
                $progress.hide();
                $btn.prop('disabled', false).removeClass('syncing');
                $btn.find('.cs-btn-text').text('Sync Products Now');

                if (response.success) {
                    $result
                        .removeClass('error')
                        .addClass('success')
                        .html('<span class="material-icons" style="vertical-align: middle;">check_circle</span> ' + response.data.message)
                        .show();

                    // Update the synced count on the page
                    if (response.data.synced) {
                        $('.cs-sync-stat:last .cs-sync-number').text(response.data.synced);
                    }

                    // Reload after 2 seconds to show updated stats
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $result
                        .removeClass('success')
                        .addClass('error')
                        .html('<span class="material-icons" style="vertical-align: middle;">error</span> ' + (response.data && response.data.message ? response.data.message : 'Sync failed'))
                        .show();
                }
            },
            error: function(xhr, status, error) {
                $progress.hide();
                $btn.prop('disabled', false).removeClass('syncing');
                $btn.find('.cs-btn-text').text('Sync Products Now');

                // Provide helpful error message based on status code
                var errorMsg = 'Connection error';
                if (xhr.status === 400) {
                    errorMsg = 'Invalid request (400). Please refresh the page and try again.';
                    console.error('Commerce Studio Sync Error: 400 - nonce or request may be invalid', {
                        nonce: config.nonce ? 'present' : 'missing',
                        ajaxUrl: config.ajaxUrl
                    });
                } else if (xhr.status === 403) {
                    errorMsg = 'Session expired (403). Please refresh the page.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error (500). Please check the error logs.';
                } else if (xhr.status === 0) {
                    errorMsg = 'Network error. Please check your connection.';
                } else {
                    errorMsg = 'Error ' + xhr.status + ': ' + error;
                }

                $result
                    .removeClass('success')
                    .addClass('error')
                    .html('<span class="material-icons" style="vertical-align: middle;">error</span> ' + errorMsg)
                    .show();
            }
        });
    });

    // Orders & Customers sync button handler
    $('#cs-sync-orders-btn').on('click', function() {
        var $btn = $(this);
        var $progress = $('#cs-sync-orders-progress');
        var $result = $('#cs-sync-orders-result');

        if (!config.nonce) {
            $result
                .addClass('error')
                .html('<span class="material-icons" style="vertical-align: middle;">error</span> Session expired. Please refresh the page and try again.')
                .show();
            return;
        }

        $btn.prop('disabled', true).addClass('syncing');
        $btn.find('.cs-btn-text').text('Syncing...');
        $progress.show();
        $result.hide();

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cs_sync_orders_customers',
                nonce: config.nonce
            },
            success: function(response) {
                $progress.hide();
                $btn.prop('disabled', false).removeClass('syncing');
                $btn.find('.cs-btn-text').text('Sync Orders & Customers');

                if (response.success) {
                    $result
                        .removeClass('error')
                        .addClass('success')
                        .html('<span class="material-icons" style="vertical-align: middle;">check_circle</span> ' + response.data.message)
                        .show();

                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $result
                        .removeClass('success')
                        .addClass('error')
                        .html('<span class="material-icons" style="vertical-align: middle;">error</span> ' + (response.data && response.data.message ? response.data.message : 'Sync failed'))
                        .show();
                }
            },
            error: function(xhr, status, error) {
                $progress.hide();
                $btn.prop('disabled', false).removeClass('syncing');
                $btn.find('.cs-btn-text').text('Sync Orders & Customers');

                var errorMsg = 'Connection error';
                if (xhr.status === 403) {
                    errorMsg = 'Session expired (403). Please refresh the page.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error (500). Please check the error logs.';
                } else if (xhr.status === 0) {
                    errorMsg = 'Network error. Please check your connection.';
                } else {
                    errorMsg = 'Error ' + xhr.status + ': ' + error;
                }

                $result
                    .removeClass('success')
                    .addClass('error')
                    .html('<span class="material-icons" style="vertical-align: middle;">error</span> ' + errorMsg)
                    .show();
            }
        });
    });

    // Test connection button handler
    $('#cs-test-connection').on('click', function() {
        var $btn = $(this);
        var $status = $('#cs-connection-status');

        $btn.prop('disabled', true);
        $status.text('Testing...');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'cs_test_connection',
                nonce: config.nonce
            },
            success: function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    $status.html('<span style="color: #059669;">✓ Connected</span>');
                } else {
                    $status.html('<span style="color: #dc2626;">✗ ' + (response.data && response.data.message ? response.data.message : 'Failed') + '</span>');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                var errMsg = xhr.status === 400 ? 'Session invalid - refresh page' : 'Connection error';
                $status.html('<span style="color: #dc2626;">✗ ' + errMsg + '</span>');
            }
        });
    });
});
</script>
