<?php
/**
 * Commerce Studio - Settings Page
 * Matches Shopify app SettingsPage.tsx
 */

if (!defined('WPINC')) {
    die;
}

$environment = CS_Config::get_environment();
$api_key = get_option('cs_api_key', '');
$store_id = get_option('cs_store_id', CS_Config::get_store_url());
$store_url_override = get_option('cs_store_url_override', '');
?>

<div class="wrap cs-admin">
    <div class="cs-header">
        <div class="cs-header-content">
            <a href="<?php echo admin_url('admin.php?page=commerce-studio'); ?>" class="cs-back-link">
                <span class="material-icons">arrow_back</span>
                Back
            </a>
            <h1>
                <span class="material-icons">settings</span>
                Settings
            </h1>
            <p class="cs-subtitle">Configure your Commerce Studio integration</p>
        </div>
    </div>

    <form method="post" action="options.php" id="cs-settings-form">
        <?php settings_fields('cs_settings'); ?>

        <!-- Connection Settings -->
        <div class="cs-card">
            <div class="cs-card-header">
                <h2>
                    <span class="material-icons">cloud</span>
                    Connection Settings
                </h2>
            </div>
            <div class="cs-card-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cs_environment">Environment</label>
                        </th>
                        <td>
                            <select name="cs_environment" id="cs_environment" class="cs-select">
                                <option value="staging" <?php selected($environment, 'staging'); ?>>
                                    Staging (Testing)
                                </option>
                                <option value="production" <?php selected($environment, 'production'); ?>>
                                    Production (Live)
                                </option>
                            </select>
                            <p class="description">
                                Use <strong>Staging</strong> for testing, <strong>Production</strong> for live stores.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cs_api_key">API Key</label>
                        </th>
                        <td>
                            <input type="text" name="cs_api_key" id="cs_api_key"
                                   value="<?php echo esc_attr($api_key); ?>"
                                   class="regular-text" placeholder="Enter your API key">
                            <p class="description">
                                Your Commerce Studio API key from the partner dashboard.
                                <a href="<?php echo CS_Config::get_dashboard_url(); ?>" target="_blank">Get API Key</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cs_store_id">Store ID</label>
                        </th>
                        <td>
                            <input type="text" name="cs_store_id" id="cs_store_id"
                                   value="<?php echo esc_attr($store_id); ?>"
                                   class="regular-text" readonly>
                            <p class="description">
                                Unique identifier for your store (set during account connection).
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cs_store_url_override">Public Store URL</label>
                        </th>
                        <td>
                            <input type="url" name="cs_store_url_override" id="cs_store_url_override"
                                   value="<?php echo esc_attr($store_url_override); ?>"
                                   class="regular-text" placeholder="<?php echo esc_attr(get_site_url()); ?>">
                            <p class="description">
                                Set this if your WordPress site URL differs from your public domain
                                (e.g., on GoDaddy, WP Engine, or Cloudways staging).
                                Leave blank to use the default.
                            </p>
                            <?php if (CS_Config::has_internal_hostname()): ?>
                            <p class="description" style="color: #b45309; margin-top: 4px;">
                                <span class="dashicons dashicons-warning" style="font-size: 14px; vertical-align: text-bottom;"></span>
                                Your current WordPress URL (<code><?php echo esc_html(get_site_url()); ?></code>) appears to be an internal hosting URL. We recommend setting your public domain here.
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <div class="cs-connection-status">
                    <h4>Connection Status</h4>
                    <div class="cs-status-row">
                        <span class="cs-status-label">API Endpoint:</span>
                        <code><?php echo esc_html(CS_Config::get_api_url()); ?></code>
                    </div>
                    <div class="cs-status-row">
                        <span class="cs-status-label">Widget URL:</span>
                        <code><?php echo esc_html(CS_Config::get_widget_url()); ?></code>
                    </div>
                    <div class="cs-status-row">
                        <button type="button" class="button" id="cs-test-connection-btn">
                            <span class="material-icons">sync</span>
                            Test Connection
                        </button>
                        <span id="cs-test-result"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Automatic Sync Schedule -->
        <div class="cs-card">
            <div class="cs-card-header">
                <h2>
                    <span class="material-icons">schedule</span>
                    Automatic Sync Schedule
                </h2>
            </div>
            <div class="cs-card-body">
                <p class="description" style="margin-bottom: 16px;">
                    Commerce Studio automatically syncs your products, orders, and customers on a recurring schedule.
                    The default schedule is free. Faster frequencies consume tokens from your account balance.
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cs_sync_frequency">Sync Frequency</label>
                        </th>
                        <td>
                            <?php $current_frequency = get_option('cs_sync_frequency', 'default'); ?>
                            <select id="cs_sync_frequency" class="cs-select">
                                <option value="default" <?php selected($current_frequency, 'default'); ?>>
                                    Default (Free) &mdash; Products 2x/day, Orders every 6h
                                </option>
                                <option value="every_four_hours" <?php selected($current_frequency, 'every_four_hours'); ?>>
                                    Every 4 hours &mdash; ~12 tokens/day
                                </option>
                                <option value="every_two_hours" <?php selected($current_frequency, 'every_two_hours'); ?>>
                                    Every 2 hours &mdash; ~36 tokens/day
                                </option>
                                <option value="hourly" <?php selected($current_frequency, 'hourly'); ?>>
                                    Hourly &mdash; ~96 tokens/day
                                </option>
                            </select>
                            <p class="description">
                                Faster sync frequencies keep your store data more current but consume tokens.
                                Manual sync is always available at no cost.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Current Schedule</th>
                        <td>
                            <?php
                            $next_product = wp_next_scheduled('cs_scheduled_product_sync');
                            $next_orders  = wp_next_scheduled('cs_scheduled_orders_sync');
                            ?>
                            <div class="cs-status-row" style="margin-bottom: 8px;">
                                <span class="cs-status-label">Next product sync:</span>
                                <?php if ($next_product): ?>
                                    <strong><?php echo esc_html(date('M j, Y g:i A', $next_product)); ?></strong>
                                <?php else: ?>
                                    <em>Not scheduled</em>
                                <?php endif; ?>
                            </div>
                            <div class="cs-status-row">
                                <span class="cs-status-label">Next orders sync:</span>
                                <?php if ($next_orders): ?>
                                    <strong><?php echo esc_html(date('M j, Y g:i A', $next_orders)); ?></strong>
                                <?php else: ?>
                                    <em>Not scheduled</em>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                </table>

                <?php $skip_reason = get_option('cs_last_sync_skip_reason', ''); ?>
                <?php if ($skip_reason === 'insufficient_tokens'): ?>
                <div class="notice notice-warning inline" style="margin: 12px 0;">
                    <p>
                        <span class="material-icons" style="font-size: 16px; vertical-align: text-bottom;">warning</span>
                        <strong>Last scheduled sync was skipped</strong> due to insufficient tokens.
                        Reduce your sync frequency or add tokens to your account.
                    </p>
                </div>
                <?php endif; ?>

                <div style="margin-top: 12px;">
                    <button type="button" class="button button-primary" id="cs-save-sync-frequency">
                        <span class="material-icons" style="font-size: 16px; vertical-align: text-bottom;">save</span>
                        Save Sync Frequency
                    </button>
                    <span id="cs-sync-frequency-result" style="margin-left: 10px;"></span>
                </div>

                <p class="description" style="margin-top: 12px;">
                    <strong>Note:</strong> WP-Cron relies on site traffic to trigger scheduled events.
                    For reliable sync timing, consider setting up a system cron job (e.g., <code>wget</code>)
                    to call <code>wp-cron.php</code> at regular intervals.
                </p>
            </div>
        </div>

        <!-- Advanced Settings -->
        <div class="cs-card">
            <div class="cs-card-header">
                <h2>
                    <span class="material-icons">tune</span>
                    Advanced Settings
                </h2>
            </div>
            <div class="cs-card-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">Debug Mode</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cs_debug_mode" value="1"
                                       <?php checked(get_option('cs_debug_mode', false)); ?>>
                                Enable debug logging (for troubleshooting)
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cache</th>
                        <td>
                            <button type="button" class="button" id="cs-clear-cache">
                                <span class="material-icons">delete_sweep</span>
                                Clear Plugin Cache
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Updates</th>
                        <td>
                            <button type="button" class="button" id="cs-check-updates">
                                <span class="material-icons">refresh</span>
                                Check for Updates
                            </button>
                            <span id="cs-update-status" style="margin-left: 10px;"></span>
                            <p class="description">Current version: <?php echo CS_VERSION; ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="cs-form-actions">
            <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
            <button type="button" class="button" id="cs-reset-settings">
                Reset to Defaults
            </button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#cs-save-sync-frequency').on('click', function() {
        var $btn = $(this);
        var $result = $('#cs-sync-frequency-result');
        var frequency = $('#cs_sync_frequency').val();

        $btn.prop('disabled', true);
        $result.text('Saving...').css('color', '#666');

        $.ajax({
            url: (window.commerceStudio && window.commerceStudio.ajaxUrl) || ajaxurl,
            type: 'POST',
            data: {
                action: 'cs_save_sync_frequency',
                nonce: (window.commerceStudio && window.commerceStudio.nonce) || '<?php echo wp_create_nonce("cs_admin_nonce"); ?>',
                frequency: frequency
            },
            success: function(response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    $result.html('<span style="color: #059669;">&#10003; ' + response.data.message + '</span>');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    $result.html('<span style="color: #dc2626;">&#10007; ' + (response.data && response.data.message ? response.data.message : 'Failed') + '</span>');
                }
            },
            error: function() {
                $btn.prop('disabled', false);
                $result.html('<span style="color: #dc2626;">&#10007; Connection error</span>');
            }
        });
    });
});
</script>
