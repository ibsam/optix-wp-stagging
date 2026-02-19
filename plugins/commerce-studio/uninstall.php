<?php
/**
 * Commerce Studio Uninstall
 *
 * Fired when the plugin is deleted from WordPress admin.
 * Cleans up all plugin data and notifies Commerce Studio backend.
 *
 * @package CommerceStudio
 */

// Exit if not called by WordPress uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get stored data before deletion
$store_url = get_site_url();
$api_key = get_option('cs_api_key', '');
$environment = get_option('cs_environment', 'production'); // Default to production
$installed_apps = get_option('cs_installed_apps', []);
$store_id = get_option('cs_store_id', '');

// Determine API URL based on environment
$api_url = $environment === 'production'
    ? 'https://woocommerce.commerce-studio.com'
    : 'https://commerce-studio-woocommerce-staging-353252826752.us-central1.run.app';

// Notify backend of complete uninstall
$response = wp_remote_post($api_url . '/api/platform/disconnect', [
    'headers' => [
        'Content-Type' => 'application/json',
        'X-Store-URL' => $store_url,
        'X-API-Key' => $api_key
    ],
    'body' => json_encode([
        'store_url' => $store_url,
        'store_id' => $store_id,
        'platform' => 'woocommerce',
        'installed_apps' => $installed_apps,
        'action' => 'uninstall',
        'reason' => 'plugin_deleted',
        'cleanup_data' => true
    ]),
    'timeout' => 30
]);

// Log uninstall attempt
if (is_wp_error($response)) {
    error_log('Commerce Studio: Uninstall notification failed - ' . $response->get_error_message());
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    error_log('Commerce Studio: Uninstall notification sent, status: ' . $status_code);
}

// Delete all plugin options
delete_option('cs_environment');
delete_option('cs_api_key');
delete_option('cs_store_id');
delete_option('cs_onboarding_complete');
delete_option('cs_installed_apps');
delete_option('cs_widget_position');
delete_option('cs_consumer_key');
delete_option('cs_consumer_secret');
delete_option('cs_sync_frequency');
delete_option('cs_last_sync_skip_reason');

// Delete app-specific configurations
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'cs_app_config_%'");

// Delete transients
delete_transient('cs_marketplace_apps');
delete_transient('cs_analytics_cache');

// Clear any scheduled events
wp_clear_scheduled_hook('cs_sync_products');
wp_clear_scheduled_hook('cs_sync_analytics');
wp_clear_scheduled_hook('cs_scheduled_product_sync');
wp_clear_scheduled_hook('cs_scheduled_orders_sync');

error_log('Commerce Studio: Plugin completely uninstalled for ' . $store_url);
