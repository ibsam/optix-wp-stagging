<?php
/**
 * Plugin Name: Commerce Studio
 * Plugin URI: https://commerce.varai.ai
 * Description: AI-powered eyewear retail solutions for WooCommerce - Virtual Try-On, PD Measurement, AI Recommendations, and more.
 * Version: 1.8.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: VARAI
 * Author URI: https://commerce.varai.ai
 * License: GPL-2.0+
 * Text Domain: commerce-studio
 * WC requires at least: 7.0
 * WC tested up to: 10.3
 */

if (!defined('WPINC')) {
    die;
}

define('CS_VERSION', '1.8.0');

/**
 * Declare HPOS (High-Performance Order Storage) compatibility
 * Required for WooCommerce 8.2+
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});
/**
 * Custom cron intervals for scheduled sync
 */
add_filter('cron_schedules', function($schedules) {
    $schedules['every_six_hours'] = [
        'interval' => 21600,
        'display'  => __('Every 6 Hours', 'commerce-studio')
    ];
    $schedules['every_four_hours'] = [
        'interval' => 14400,
        'display'  => __('Every 4 Hours', 'commerce-studio')
    ];
    $schedules['every_two_hours'] = [
        'interval' => 7200,
        'display'  => __('Every 2 Hours', 'commerce-studio')
    ];
    return $schedules;
});

define('CS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Environment Configuration
 *
 * Uses dynamic endpoint discovery to automatically determine the correct
 * regional endpoints for the tenant. This eliminates hardcoded URLs and
 * ensures EU customers get EU endpoints for GDPR compliance.
 */
class CS_Config {
    // Endpoint discovery URL (always points to admin portal which handles multi-region)
    const ENDPOINT_DISCOVERY_URL = 'https://commerce-studio-admin-portal-353252826752.us-central1.run.app';

    // Cache key and duration (24 hours)
    const CACHE_KEY = 'cs_endpoints_cache';
    const CACHE_DURATION = 86400;

    // Fallback production URLs (US region)
    const FALLBACK_API_URL = 'https://commerce-studio-woocommerce-production-353252826752.us-central1.run.app';
    const FALLBACK_WIDGET_URL = 'https://vto-widget-353252826752.us-central1.run.app';
    const FALLBACK_SEARCH_URL = 'https://search-widget-353252826752.us-central1.run.app';
    const FALLBACK_AI_URL = 'https://conversational-ai-353252826752.us-central1.run.app';
    const FALLBACK_DASHBOARD_URL = 'https://commerce.varai.ai';

    private static $cached_endpoints = null;

    public static function get_environment() {
        return get_option('cs_environment', 'production'); // Default to production
    }

    public static function get_tenant_id() {
        return get_option('cs_tenant_id', '');
    }

    public static function get_tenant_region() {
        return get_option('cs_tenant_region', 'us');
    }

    /**
     * Get the canonical store URL for this site.
     * Priority: manual override > stored store_id > WordPress site URL.
     *
     * On managed hosting (GoDaddy, WP Engine, Cloudways, etc.) get_site_url()
     * may return an internal hostname instead of the public domain. This
     * centralised helper ensures every outbound request and stored value uses
     * the correct URL.
     *
     * @return string
     */
    public static function get_store_url() {
        // 1. Manual admin override (highest priority)
        $override = get_option('cs_store_url_override', '');
        if (!empty($override)) {
            return rtrim($override, '/');
        }

        // 2. Stored store ID (set during account creation / OAuth)
        $store_id = get_option('cs_store_id', '');
        if (!empty($store_id)) {
            return rtrim($store_id, '/');
        }

        // 3. WordPress site URL (fallback)
        return rtrim(get_site_url(), '/');
    }

    /**
     * Known internal hostname patterns used by managed hosting providers.
     * When get_site_url() matches one of these the admin should set the
     * public URL override.
     */
    private static $internal_hostname_patterns = [
        'myftpupload.com',     // GoDaddy
        'wpengine.com',        // WP Engine
        'cloudwaysapps.com',   // Cloudways
        'kinsta.cloud',        // Kinsta
        'pantheonsite.io',     // Pantheon
        'flywheelsites.com',   // Flywheel
    ];

    /**
     * Check if the current WordPress site URL looks like a managed-hosting
     * internal hostname.
     *
     * @return bool
     */
    public static function has_internal_hostname() {
        $site_url = get_site_url();
        foreach (self::$internal_hostname_patterns as $pattern) {
            if (stripos($site_url, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all endpoints (from cache or fetch from discovery API)
     */
    public static function get_endpoints() {
        // Memory cache
        if (self::$cached_endpoints !== null) {
            return self::$cached_endpoints;
        }

        // Check WordPress transient cache
        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false) {
            self::$cached_endpoints = $cached;
            return self::$cached_endpoints;
        }

        // Fetch from discovery API
        return self::fetch_and_cache_endpoints();
    }

    /**
     * Fetch endpoints from discovery API and cache them
     */
    public static function fetch_and_cache_endpoints() {
        $tenant_id = self::get_tenant_id();
        if (empty($tenant_id)) {
            return null;
        }

        $url = self::ENDPOINT_DISCOVERY_URL . '/api/v1/tenants/' . $tenant_id . '/endpoints?platform=woocommerce';

        $response = wp_remote_get($url, [
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 10
        ]);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['endpoints'])) {
                // Cache the endpoints
                set_transient(self::CACHE_KEY, $body['endpoints'], self::CACHE_DURATION);
                self::$cached_endpoints = $body['endpoints'];

                // Store the region
                if (isset($body['region'])) {
                    update_option('cs_tenant_region', $body['region']);
                }

                return self::$cached_endpoints;
            }
        }

        return null;
    }

    /**
     * Force refresh of cached endpoints
     */
    public static function refresh_endpoints() {
        self::$cached_endpoints = null;
        delete_transient(self::CACHE_KEY);
        return self::fetch_and_cache_endpoints();
    }

    public static function get_api_url() {
        $endpoints = self::get_endpoints();
        if ($endpoints && isset($endpoints['connector'])) {
            return $endpoints['connector'];
        }
        if ($endpoints && isset($endpoints['woocommerce_connector'])) {
            return $endpoints['woocommerce_connector'];
        }
        return self::FALLBACK_API_URL;
    }

    public static function get_widget_url() {
        $endpoints = self::get_endpoints();
        if ($endpoints && isset($endpoints['vto_widget'])) {
            return $endpoints['vto_widget'];
        }
        return self::FALLBACK_WIDGET_URL;
    }

    public static function get_dashboard_url() {
        $endpoints = self::get_endpoints();
        if ($endpoints && isset($endpoints['dashboard'])) {
            return $endpoints['dashboard'];
        }
        return self::FALLBACK_DASHBOARD_URL;
    }

    public static function get_search_url() {
        $endpoints = self::get_endpoints();
        if ($endpoints && isset($endpoints['search_widget'])) {
            return $endpoints['search_widget'];
        }
        return self::FALLBACK_SEARCH_URL;
    }

    public static function get_ai_url() {
        $endpoints = self::get_endpoints();
        if ($endpoints && isset($endpoints['conversational_ai'])) {
            return $endpoints['conversational_ai'];
        }
        return self::FALLBACK_AI_URL;
    }

    public static function get_lens_journey_url() {
        $endpoints = self::get_endpoints();
        if ($endpoints && isset($endpoints['lens_journey_widget'])) {
            return $endpoints['lens_journey_widget'];
        }
        return 'https://lens-journey-353252826752.us-central1.run.app';
    }

    public static function get_pd_calculator_url() {
        $endpoints = self::get_endpoints();
        if ($endpoints && isset($endpoints['pd_calculator_widget'])) {
            return $endpoints['pd_calculator_widget'];
        }
        return 'https://pd-calculator-353252826752.us-central1.run.app';
    }

    public static function get_backend_api_url() {
        $endpoints = self::get_endpoints();
        if ($endpoints && isset($endpoints['api'])) {
            return $endpoints['api'];
        }
        return 'https://commerce-studio-backend-353252826752.us-central1.run.app';
    }
}

/**
 * Available Commerce Studio Apps
 */
class CS_Apps {
    const CACHE_KEY = 'cs_marketplace_apps';
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Get available apps from marketplace API
     * Fetches live, published apps from the Commerce Studio marketplace
     */
    public static function get_available_apps() {
        // Check transient cache first
        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $dashboard_url = CS_Config::get_dashboard_url();
            // Use the harmonized /api/v1/apps/public endpoint on main backend (synced with appCatalog.ts)
            $endpoint = $dashboard_url . '/api/v1/apps/public';

            $response = wp_remote_get($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Store-URL' => CS_Config::get_store_url()
                ],
                'timeout' => 10
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);

                if (isset($body['apps']) && is_array($body['apps'])) {
                    $apps = self::format_apps_response($body['apps']);
                    set_transient(self::CACHE_KEY, $apps, self::CACHE_TTL);
                    return $apps;
                }
            }

            error_log('Commerce Studio: Failed to fetch marketplace apps - ' .
                (is_wp_error($response) ? $response->get_error_message() : 'Status: ' . wp_remote_retrieve_response_code($response)));

        } catch (Exception $e) {
            error_log('Commerce Studio: Error fetching marketplace apps - ' . $e->getMessage());
        }

        // Return fallback apps if API fails
        return self::get_fallback_apps();
    }

    /**
     * Force refresh apps from marketplace
     */
    public static function refresh_apps() {
        delete_transient(self::CACHE_KEY);
        return self::get_available_apps();
    }

    /**
     * Format apps response from API
     * Synced with frontend/src/data/appCatalog.ts for consistent pricing/tier display
     */
    protected static function format_apps_response($apps) {
        $formatted = [];

        foreach ($apps as $app) {
            $id = $app['id'] ?? $app['slug'] ?? '';
            if (empty($id)) {
                continue;
            }

            // Build pricing display string
            $price_display = $app['price'] ?? 'Included';
            $pricing = $app['pricing'] ?? null;
            $contact_sales = false;

            if ($pricing && isset($pricing['contactSales']) && $pricing['contactSales']) {
                $price_display = 'Contact Sales';
                $contact_sales = true;
            } elseif ($pricing && isset($pricing['model'])) {
                switch ($pricing['model']) {
                    case 'enterprise':
                        $price_display = 'Contact Sales';
                        $contact_sales = true;
                        break;
                    case 'tiered':
                        if (isset($pricing['tiers']) && count($pricing['tiers']) > 0) {
                            $first_tier = $pricing['tiers'][0];
                            if (isset($first_tier['price']) && $first_tier['price'] === 0) {
                                $price_display = 'Free - ' . ($app['price'] ?? 'varies');
                            } else {
                                $price_display = 'From $' . ($first_tier['price'] ?? '0') . '/mo';
                            }
                        }
                        break;
                    case 'subscription':
                        $period = $pricing['period'] ?? 'month';
                        $price_display = '$' . ($pricing['price'] ?? 0) . '/' . substr($period, 0, 2);
                        break;
                }
            }

            $formatted[] = [
                'id' => $id,
                'slug' => $app['slug'] ?? $id,
                'name' => $app['name'] ?? $app['title'] ?? $id,
                'description' => $app['description'] ?? $app['short_description'] ?? '',
                'icon' => $app['icon'] ?? self::map_category_to_icon($app['category'] ?? ''),
                'category' => $app['category'] ?? 'general',
                'widget' => $app['widget_type'] ?? $app['widget'] ?? $app['slug'] ?? $id,
                'version' => $app['version'] ?? '1.0.0',
                // Pricing and tier info (synced with appCatalog.ts)
                'tier' => $app['tier'] ?? 'core',
                'pricing' => $pricing,
                'price' => $price_display,
                'contactSales' => $contact_sales || ($app['contactSales'] ?? false),
                'badge' => $app['badge'] ?? null,
                // Other metadata
                'image_url' => $app['image_url'] ?? $app['icon_url'] ?? null,
                'developer' => $app['developer'] ?? $app['author'] ?? 'VARAi Studio',
                'rating' => $app['rating'] ?? null,
                'reviewCount' => $app['reviewCount'] ?? null,
                'installCount' => $app['installCount'] ?? null,
                'features' => $app['features'] ?? [],
                'featured' => $app['featured'] ?? false,
                'new' => $app['new'] ?? false
            ];
        }

        return $formatted;
    }

    /**
     * Map category to Material Icons name
     */
    protected static function map_category_to_icon($category) {
        $icon_map = [
            'engagement' => 'visibility',
            'vto' => 'visibility',
            'try-on' => 'visibility',
            'eyewear' => 'visibility',
            'support' => 'chat',
            'chat' => 'chat',
            'avatar' => 'person',
            'conversion' => 'trending_up',
            'recommendations' => 'psychology',
            'ai-recommendations' => 'psychology',
            'sizing' => 'straighten',
            'lens-selection' => 'remove_red_eye',
            'analytics' => 'analytics',
            'marketing' => 'campaign',
            'shipping' => 'local_shipping',
            'payments' => 'payments',
            'inventory' => 'inventory_2'
        ];

        return $icon_map[strtolower($category)] ?? 'apps';
    }

    /**
     * Get fallback apps when API is unavailable
     * Synced with frontend/src/data/appCatalog.ts for consistent pricing/tier display
     */
    protected static function get_fallback_apps() {
        $base_url = 'https://commerce.varai.ai';
        return [
            // CORE APPS (included in subscription)
            [
                'id' => 'b1000000-0000-0000-0000-000000000001',
                'slug' => 'virtual-try-on',
                'name' => 'Virtual Try-On',
                'description' => 'Real-time AR frame visualization with MediaPipe face tracking.',
                'icon' => 'visibility',
                'category' => 'AR/VR',
                'widget' => 'virtual-try-on',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'badge' => 'Essential',
                'developer' => 'VARAi Studio',
                'rating' => 4.9,
                'features' => ['Face detection', 'Real-time AR try-on', 'Multiple frame support'],
                'demoUrl' => $base_url . '/products/vto-technology',
                'faqUrl' => $base_url . '/products/vto-technology#faq'
            ],
            [
                'id' => 'b1000000-0000-0000-0000-000000000002',
                'slug' => 'pd-measurement',
                'name' => 'PD Measurement Tool',
                'description' => 'Accurate pupillary distance measurement using AI-powered camera analysis.',
                'icon' => 'straighten',
                'category' => 'Measurement Tools',
                'widget' => 'pd-measurement',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'badge' => 'Essential',
                'developer' => 'VARAi Studio',
                'rating' => 4.9,
                'features' => ['AI measurement', 'Camera calibration', 'Accuracy validation'],
                'demoUrl' => $base_url . '/products/pd-calculator',
                'faqUrl' => $base_url . '/products/pd-calculator#faq'
            ],
            [
                'id' => 'b1000000-0000-0000-0000-000000000004',
                'slug' => 'face-shape-analyzer',
                'name' => 'AI Face Shape Analyzer',
                'description' => 'AI-powered face shape detection with personalized frame recommendations.',
                'icon' => 'face',
                'category' => 'AI & Machine Learning',
                'widget' => 'face-shape-analyzer',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'developer' => 'VARAi Studio',
                'rating' => 4.7,
                'features' => ['Face shape detection', 'Frame recommendations', 'Style matching'],
                'demoUrl' => $base_url . '/products/face-shape-analysis',
                'faqUrl' => $base_url . '/products/face-shape-analysis#faq'
            ],
            [
                'id' => 'b1000000-0000-0000-0000-000000000009',
                'slug' => '3d-viewer',
                'name' => '3D Product Viewer',
                'description' => 'Interactive 3D frame visualization with 360-degree rotation and zoom.',
                'icon' => '3d_rotation',
                'category' => 'Product Display',
                'widget' => '3d-viewer',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'developer' => 'VARAi Studio',
                'rating' => 4.6,
                'features' => ['360° rotation', 'Zoom controls', 'Mobile gestures'],
                'demoUrl' => $base_url . '/products/3d-product-viewer',
                'faqUrl' => $base_url . '/products/3d-product-viewer#faq'
            ],
            [
                'id' => 'b1000000-0000-0000-0000-000000000003',
                'slug' => 'lens-journey',
                'name' => 'Lens Customization Journey',
                'description' => 'Complete lens customization journey with dynamic pricing and AR preview.',
                'icon' => 'remove_red_eye',
                'category' => 'Customer Experience',
                'widget' => 'lens-journey',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'badge' => 'NEW',
                'developer' => 'VARAi Studio',
                'rating' => 4.9,
                'new' => true,
                'features' => ['Prescription management', 'AR preview', 'Dynamic pricing'],
                'demoUrl' => $base_url . '/products/eyewear-journey',
                'faqUrl' => $base_url . '/products/eyewear-journey#faq'
            ],
            [
                'id' => 'b1000000-0000-0000-0000-000000000005',
                'slug' => 'conversational-ai',
                'name' => 'AI Style Assistant',
                'description' => 'Text-based AI chat assistant for personalized eyewear recommendations.',
                'icon' => 'chat',
                'category' => 'AI & Machine Learning',
                'widget' => 'conversational-ai',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'developer' => 'VARAi Studio',
                'rating' => 4.7,
                'features' => ['Multi-agent orchestration', 'Language detection', 'Photo analysis'],
                'demoUrl' => $base_url . '/products/ai-recommendations',
                'faqUrl' => $base_url . '/products/ai-recommendations#faq'
            ],
            // PREMIUM APPS (tiered pricing)
            [
                'id' => 'b2000000-0000-0000-0000-000000000001',
                'slug' => 'avatar-2d',
                'name' => '2D Avatar Chat',
                'description' => 'AI-powered conversational avatar with Vertex AI intelligence and ElevenLabs voice.',
                'icon' => 'person',
                'category' => 'AI & Machine Learning',
                'widget' => 'avatar-2d',
                'tier' => 'premium',
                'pricing' => [
                    'model' => 'tiered',
                    'tiers' => [
                        ['name' => 'Starter', 'price' => 179],
                        ['name' => 'Professional', 'price' => 349],
                        ['name' => 'Enterprise', 'price' => 'custom']
                    ],
                    'tokenCost' => 35
                ],
                'price' => 'From $179/mo',
                'contactSales' => false,
                'badge' => 'Premium',
                'developer' => 'VARAi Studio',
                'rating' => 4.9,
                'features' => ['Vertex AI chat', 'ElevenLabs voice', 'Lip-sync animation'],
                'demoUrl' => $base_url . '/products/3d-avatar',
                'faqUrl' => $base_url . '/products/3d-avatar#faq'
            ],
            [
                'id' => 'b2000000-0000-0000-0000-000000000002',
                'slug' => 'avatar-3d',
                'name' => 'HeyGen 3D Avatar',
                'description' => 'Ultra-realistic 3D avatar consultations powered by HeyGen with real-time lip-sync.',
                'icon' => 'face_3',
                'category' => 'AI & Machine Learning',
                'widget' => 'avatar-3d',
                'tier' => 'enterprise',
                'pricing' => [
                    'model' => 'enterprise',
                    'contactSales' => true,
                    'tiers' => [['name' => 'Enterprise', 'price' => 'custom']],
                    'tokenCost' => 75
                ],
                'price' => 'Contact Sales',
                'contactSales' => true,
                'badge' => 'Enterprise',
                'developer' => 'VARAi Studio',
                'rating' => 4.9,
                'features' => ['HeyGen streaming', 'Vertex AI intelligence', 'Real-time lip-sync'],
                'demoUrl' => $base_url . '/products/3d-avatar',
                'faqUrl' => $base_url . '/products/3d-avatar#faq'
            ],
            [
                'id' => 'b3000000-0000-0000-0000-000000000001',
                'slug' => 'bopis-reserve-pickup',
                'name' => 'Reserve & Pickup (BOPIS)',
                'description' => 'Buy Online, Pick Up In-Store with real-time availability.',
                'icon' => 'local_shipping',
                'category' => 'Retail Operations',
                'widget' => 'bopis-reserve-pickup',
                'tier' => 'premium',
                'pricing' => [
                    'model' => 'tiered',
                    'tiers' => [
                        ['name' => 'Essential', 'price' => 0],
                        ['name' => 'Professional', 'price' => 79],
                        ['name' => 'Enterprise', 'price' => 199]
                    ]
                ],
                'price' => 'Free - $199/mo',
                'contactSales' => false,
                'developer' => 'VARAi Studio',
                'rating' => 4.7,
                'features' => ['Store locator', 'Real-time availability', 'Pickup scheduling'],
                'demoUrl' => $base_url . '/products/bopis',
                'faqUrl' => $base_url . '/products/bopis#faq'
            ],
            [
                'id' => 'b1000000-0000-0000-0000-000000000010',
                'slug' => 'fitting-height',
                'name' => 'Fitting Height Calculator',
                'description' => 'Precise fitting height measurement for progressive and bifocal lenses.',
                'icon' => 'height',
                'category' => 'Measurement Tools',
                'widget' => 'fitting-height',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'developer' => 'VARAi Studio',
                'rating' => 4.8,
                'features' => ['Progressive lens optimization', 'Visual guides', 'Accuracy validation'],
                'demoUrl' => $base_url . '/products/fitting-height-product',
                'faqUrl' => $base_url . '/products/fitting-height-product#faq'
            ],
            [
                'id' => 'b1000000-0000-0000-0000-000000000011',
                'slug' => 'size-recommender',
                'name' => 'AI Size Recommender',
                'description' => 'Intelligent frame size recommendations based on face measurements.',
                'icon' => 'straighten',
                'category' => 'AI & Machine Learning',
                'widget' => 'size-recommender',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'developer' => 'VARAi Studio',
                'rating' => 4.7,
                'features' => ['Face analysis', 'Size matching', 'Fit scoring'],
                'demoUrl' => $base_url . '/products/size-recommendation',
                'faqUrl' => $base_url . '/products/size-recommendation#faq'
            ],
            [
                'id' => 'b1000000-0000-0000-0000-000000000012',
                'slug' => 'intelligent-search',
                'name' => 'Intelligent Search',
                'description' => 'AI-powered product search with natural language understanding.',
                'icon' => 'search',
                'category' => 'AI & Machine Learning',
                'widget' => 'intelligent-search',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'developer' => 'VARAi Studio',
                'rating' => 4.6,
                'features' => ['Natural language search', 'Semantic matching', 'Visual search'],
                'demoUrl' => $base_url . '/products/product-discovery',
                'faqUrl' => $base_url . '/products/product-discovery#faq'
            ],
            // ALPHA - Body Measurement
            [
                'id' => 'b4000000-0000-0000-0000-000000000001',
                'slug' => 'body-measurement',
                'name' => 'Body Measurement',
                'description' => 'AI-powered body and face measurement for custom-fit eyewear and apparel.',
                'icon' => 'accessibility_new',
                'category' => 'Measurement Tools',
                'widget' => 'body-measurement',
                'tier' => 'core',
                'pricing' => ['model' => 'included', 'tokenCost' => 0],
                'price' => 'Included',
                'contactSales' => false,
                'badge' => 'Alpha',
                'developer' => 'VARAi Studio',
                'rating' => null,
                'features' => ['Face dimensions', 'Head circumference', 'Body measurements'],
                'demoUrl' => $base_url . '/products/body-measurement',
                'faqUrl' => $base_url . '/products/body-measurement#faq'
            ]
        ];
    }

    public static function get_installed_apps() {
        $apps = get_option('cs_installed_apps', []);
        // Guard against corrupted DB value (empty string instead of array)
        if (!is_array($apps)) {
            $apps = [];
            update_option('cs_installed_apps', $apps);
        }
        return $apps;
    }

    public static function install_app($app_id) {
        $installed = self::get_installed_apps();
        if (!in_array($app_id, $installed)) {
            $installed[] = $app_id;
            update_option('cs_installed_apps', $installed);
        }
        return true;
    }

    public static function uninstall_app($app_id) {
        $installed = self::get_installed_apps();
        $installed = array_filter($installed, fn($id) => $id !== $app_id);
        update_option('cs_installed_apps', array_values($installed));
        return true;
    }

    public static function is_app_installed($app_id) {
        return in_array($app_id, self::get_installed_apps());
    }
}

/**
 * Main Plugin Class
 */
class Commerce_Studio {
    private static $instance = null;

    // Partner Portal OAuth URL
    const PARTNER_PORTAL_URL = 'https://partner-portal-ddtojwjn7a-uc.a.run.app';

    /**
     * Map app slugs to server widget file IDs
     * These must match the validWidgets whitelist in routes/widgets.js
     */
    private static function get_widget_file_map() {
        return [
            // Slug-based keys (canonical)
            'virtual-try-on'       => 'vto-widget.js',
            'pd-measurement'       => 'pd-calculator.js',
            'face-shape-analyzer'  => 'face-shape-analyzer.js',
            '3d-viewer'            => '3d-viewer.js',
            'lens-journey'         => 'lens-journey.js',
            'conversational-ai'    => 'chat-widget.js',
            'ai-sales-agent'       => 'chat-widget.js',
            'avatar-2d'            => 'chat-widget.js',
            'avatar-3d'            => 'chat-widget.js',
            'bopis-reserve-pickup' => 'bopis-widget.js',
            'fitting-height'       => 'fitting-height.js',
            'size-recommender'     => 'size-recommender.js',
            'intelligent-search'   => 'intelligent-search.js',
            'body-measurement'     => 'body-measurement.js',

            // UUID-based keys (backward compatibility for existing installs)
            'b1000000-0000-0000-0000-000000000001' => 'vto-widget.js',          // virtual-try-on
            'b1000000-0000-0000-0000-000000000002' => 'pd-calculator.js',       // pd-measurement
            'b1000000-0000-0000-0000-000000000004' => 'face-shape-analyzer.js', // face-shape-analyzer
            'b1000000-0000-0000-0000-000000000009' => '3d-viewer.js',           // 3d-viewer
            'b1000000-0000-0000-0000-000000000003' => 'lens-journey.js',        // lens-journey
            'b1000000-0000-0000-0000-000000000005' => 'chat-widget.js',         // conversational-ai
            'b2000000-0000-0000-0000-000000000010' => 'chat-widget.js',         // ai-sales-agent
            'b2000000-0000-0000-0000-000000000001' => 'chat-widget.js',         // avatar-2d
            'b2000000-0000-0000-0000-000000000002' => 'chat-widget.js',         // avatar-3d
            'b3000000-0000-0000-0000-000000000001' => 'bopis-widget.js',        // bopis-reserve-pickup
            'b1000000-0000-0000-0000-000000000010' => 'fitting-height.js',      // fitting-height
            'b1000000-0000-0000-0000-000000000011' => 'size-recommender.js',    // size-recommender
            'b1000000-0000-0000-0000-000000000012' => 'intelligent-search.js',  // intelligent-search
        ];
    }

    /**
     * Map position config values to WooCommerce hooks
     */
    private static function get_wc_hook_for_position($position) {
        $map = [
            'after-title'       => ['woocommerce_single_product_summary', 6],
            'after-price'       => ['woocommerce_single_product_summary', 11],
            'before-cart'       => ['woocommerce_before_add_to_cart_form', 10],
            'after-cart'        => ['woocommerce_after_add_to_cart_form', 10],
            'after-description' => ['woocommerce_after_single_product_summary', 11],
        ];
        return $map[$position] ?? $map['before-cart'];
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'handle_oauth_callback']);
        add_action('admin_init', [$this, 'handle_deep_link_install']);

        // AJAX handlers
        add_action('wp_ajax_cs_install_app', [$this, 'ajax_install_app']);
        add_action('wp_ajax_cs_uninstall_app', [$this, 'ajax_uninstall_app']);
        add_action('wp_ajax_cs_save_app_config', [$this, 'ajax_save_app_config']);
        add_action('wp_ajax_cs_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_cs_complete_onboarding', [$this, 'ajax_complete_onboarding']);

        // Onboarding AJAX handlers
        add_action('wp_ajax_cs_create_account', [$this, 'ajax_create_account']);
        add_action('wp_ajax_cs_connect_account', [$this, 'ajax_connect_account']);
        add_action('wp_ajax_cs_sync_products', [$this, 'ajax_sync_products']);
        add_action('wp_ajax_cs_sync_orders_customers', [$this, 'ajax_sync_orders_customers']);
        add_action('wp_ajax_cs_embed_apps', [$this, 'ajax_embed_apps']);
        add_action('wp_ajax_cs_refresh_apps', [$this, 'ajax_refresh_apps']);

        // Scheduled events for background sync
        add_action('cs_auto_sync_products_after_oauth', [$this, 'background_sync_products']);

        // Recurring scheduled sync hooks
        add_action('cs_scheduled_product_sync', [$this, 'run_scheduled_product_sync']);
        add_action('cs_scheduled_orders_sync', [$this, 'run_scheduled_orders_sync']);

        // AJAX handler for sync frequency setting
        add_action('wp_ajax_cs_save_sync_frequency', [$this, 'ajax_save_sync_frequency']);

        // Ensure WC credentials are registered with connector after upgrade
        add_action('admin_init', [$this, 'maybe_register_credentials_on_upgrade']);

        // Warn if WordPress site URL looks like an internal managed-hosting hostname
        add_action('admin_notices', [$this, 'maybe_show_internal_hostname_notice']);

        // Frontend widget rendering - dynamic hooks based on per-widget position config
        add_action('wp', [$this, 'setup_widget_hooks']);
        add_shortcode('commerce_studio', [$this, 'shortcode_handler']);
    }

    /**
     * Background product sync (triggered by scheduled event)
     */
    public function background_sync_products() {
        $result = $this->sync_products_to_commerce_studio();
        error_log('Commerce Studio: Background sync complete - ' .
            ($result['success'] ? 'Success: ' . ($result['synced'] ?? 0) . ' products' : 'Failed: ' . $result['message']));
    }

    /**
     * Run scheduled product sync (WP-Cron recurring event)
     * Checks token availability for paid tiers before syncing
     */
    public function run_scheduled_product_sync() {
        $frequency = get_option('cs_sync_frequency', 'default');

        // Dedup: skip if Cloud Scheduler already synced within 30 minutes
        if ($this->was_recently_synced(30)) {
            error_log('Commerce Studio: Scheduled product sync skipped - recently synced by Cloud Scheduler');
            return;
        }

        // Paid tiers require token availability
        if ($frequency !== 'default' && !$this->check_token_availability()) {
            update_option('cs_last_sync_skip_reason', 'insufficient_tokens');
            error_log('Commerce Studio: Scheduled product sync skipped - insufficient tokens');
            return;
        }

        delete_option('cs_last_sync_skip_reason');
        $result = $this->sync_products_to_commerce_studio();
        error_log('Commerce Studio: Scheduled product sync - ' .
            ($result['success'] ? 'Success: ' . ($result['synced'] ?? 0) . ' products' : 'Failed: ' . $result['message']));

        if ($frequency !== 'default') {
            $this->record_sync_token_usage('product_sync');
        }
    }

    /**
     * Run scheduled orders/customers sync (WP-Cron recurring event)
     * Checks token availability for paid tiers before syncing
     */
    public function run_scheduled_orders_sync() {
        $frequency = get_option('cs_sync_frequency', 'default');

        // Dedup: skip if Cloud Scheduler already synced within 30 minutes
        if ($this->was_recently_synced(30)) {
            error_log('Commerce Studio: Scheduled orders sync skipped - recently synced by Cloud Scheduler');
            return;
        }

        if ($frequency !== 'default' && !$this->check_token_availability()) {
            update_option('cs_last_sync_skip_reason', 'insufficient_tokens');
            error_log('Commerce Studio: Scheduled orders sync skipped - insufficient tokens');
            return;
        }

        delete_option('cs_last_sync_skip_reason');
        $result = $this->sync_orders_customers_to_commerce_studio();
        error_log('Commerce Studio: Scheduled orders sync - ' .
            ($result['success'] ? 'Success' : 'Failed: ' . $result['message']));

        if ($frequency !== 'default') {
            $this->record_sync_token_usage('orders_sync');
        }
    }

    /**
     * Check if tenant has sufficient tokens for a paid sync
     * Fail-open: if backend is unreachable, allow the sync to proceed
     *
     * @return bool
     */
    private function check_token_availability() {
        $tenant_id = get_option('cs_tenant_id', '');
        $api_key = get_option('cs_api_key', '');

        if (empty($tenant_id) || empty($api_key)) {
            return true; // Fail-open
        }

        $backend_url = CS_Config::get_backend_api_url();
        $response = wp_remote_get($backend_url . '/api/usage/availability', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'X-Tenant-ID'   => $tenant_id,
                'Accept'        => 'application/json'
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            // Fail-open: network error should not block syncs
            error_log('Commerce Studio: Token availability check failed (network) - proceeding with sync');
            return true;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            // Fail-open on non-200 responses
            return true;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return !empty($body['available']);
    }

    /**
     * Check if the backend reports a recent sync (within $minutes_threshold minutes).
     * Used as dedup to avoid WP-Cron re-syncing data that Cloud Scheduler already synced.
     * Fail-open: if the check fails, allow the sync to proceed.
     *
     * @param int $minutes_threshold Minutes threshold for "recently synced"
     * @return bool True if synced recently (should skip), false otherwise
     */
    private function was_recently_synced($minutes_threshold = 30) {
        $api_key = get_option('cs_api_key', '');
        $store_url = CS_Config::get_store_url();

        if (empty($api_key) || empty($store_url)) {
            return false; // Fail-open
        }

        $api_url = CS_Config::get_api_url();
        $response = wp_remote_get($api_url . '/integrations/sync/last-timestamp?' . http_build_query([
            'platform' => 'woocommerce',
            'store'    => $store_url
        ]), [
            'headers' => [
                'X-Commerce-Studio-Key' => $api_key,
                'Accept'                => 'application/json'
            ],
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            return false; // Fail-open on network error
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return false; // Fail-open
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['last_sync_at'])) {
            return false; // Never synced
        }

        $last_sync = strtotime($body['last_sync_at']);
        if ($last_sync === false) {
            return false;
        }

        $minutes_since_sync = (time() - $last_sync) / 60;
        if ($minutes_since_sync < $minutes_threshold) {
            error_log(sprintf(
                'Commerce Studio: Dedup check - last sync was %.1f minutes ago (threshold: %d min)',
                $minutes_since_sync,
                $minutes_threshold
            ));
            return true;
        }

        return false;
    }

    /**
     * Record token usage for a completed scheduled sync
     * Non-blocking: fires and forgets so cron completes fast
     *
     * @param string $sync_type 'product_sync' or 'orders_sync'
     */
    private function record_sync_token_usage($sync_type) {
        $tenant_id = get_option('cs_tenant_id', '');
        $api_key = get_option('cs_api_key', '');
        $frequency = get_option('cs_sync_frequency', 'default');

        if (empty($tenant_id) || empty($api_key) || $frequency === 'default') {
            return;
        }

        // Quantity scales with frequency tier
        $quantity_map = [
            'every_four_hours' => 1.0,
            'every_two_hours'  => 1.5,
            'hourly'           => 2.0
        ];
        $quantity = $quantity_map[$frequency] ?? 1.0;

        $backend_url = CS_Config::get_backend_api_url();
        wp_remote_post($backend_url . '/api/billing/overage/record-usage', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
                'X-Tenant-ID'   => $tenant_id
            ],
            'body' => json_encode([
                'operation' => 'cross_region_sync',
                'quantity'  => $quantity,
                'metadata'  => [
                    'sync_type' => $sync_type,
                    'frequency' => $frequency,
                    'platform'  => 'woocommerce',
                    'store_url' => CS_Config::get_store_url()
                ]
            ]),
            'timeout'  => 10,
            'blocking' => false
        ]);
    }

    /**
     * Schedule or reschedule sync cron events based on frequency
     *
     * @param string $frequency 'default', 'every_four_hours', 'every_two_hours', or 'hourly'
     */
    public static function schedule_sync_cron($frequency = 'default') {
        // Clear existing schedules
        wp_clear_scheduled_hook('cs_scheduled_product_sync');
        wp_clear_scheduled_hook('cs_scheduled_orders_sync');

        // Map frequency to WP-Cron recurrence names
        $schedule_map = [
            'default'          => ['products' => 'twicedaily', 'orders' => 'every_six_hours'],
            'every_four_hours' => ['products' => 'every_four_hours', 'orders' => 'every_four_hours'],
            'every_two_hours'  => ['products' => 'every_two_hours', 'orders' => 'every_two_hours'],
            'hourly'           => ['products' => 'hourly', 'orders' => 'hourly']
        ];

        $schedules = $schedule_map[$frequency] ?? $schedule_map['default'];

        if (!wp_next_scheduled('cs_scheduled_product_sync')) {
            wp_schedule_event(time(), $schedules['products'], 'cs_scheduled_product_sync');
        }
        if (!wp_next_scheduled('cs_scheduled_orders_sync')) {
            wp_schedule_event(time(), $schedules['orders'], 'cs_scheduled_orders_sync');
        }
    }

    /**
     * AJAX: Save sync frequency setting
     */
    public function ajax_save_sync_frequency() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $allowed = ['default', 'every_four_hours', 'every_two_hours', 'hourly'];
        $frequency = sanitize_text_field($_POST['frequency'] ?? 'default');

        if (!in_array($frequency, $allowed, true)) {
            wp_send_json_error(['message' => 'Invalid frequency']);
        }

        update_option('cs_sync_frequency', $frequency);
        self::schedule_sync_cron($frequency);

        wp_send_json_success([
            'message'            => 'Sync frequency updated',
            'frequency'          => $frequency,
            'next_product_sync'  => wp_next_scheduled('cs_scheduled_product_sync'),
            'next_orders_sync'   => wp_next_scheduled('cs_scheduled_orders_sync')
        ]);
    }

    /**
     * Add admin menu with subpages matching Shopify app
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Commerce Studio', 'commerce-studio'),
            __('Commerce Studio', 'commerce-studio'),
            'manage_options',
            'commerce-studio',
            [$this, 'render_home_page'],
            'dashicons-visibility',
            56
        );

        // Home/Dashboard (same as main)
        add_submenu_page(
            'commerce-studio',
            __('Home', 'commerce-studio'),
            __('Home', 'commerce-studio'),
            'manage_options',
            'commerce-studio',
            [$this, 'render_home_page']
        );

        // Apps page
        add_submenu_page(
            'commerce-studio',
            __('Apps', 'commerce-studio'),
            __('Apps', 'commerce-studio'),
            'manage_options',
            'commerce-studio-apps',
            [$this, 'render_apps_page']
        );

        // Settings page
        add_submenu_page(
            'commerce-studio',
            __('Settings', 'commerce-studio'),
            __('Settings', 'commerce-studio'),
            'manage_options',
            'commerce-studio-settings',
            [$this, 'render_settings_page']
        );

        // Onboarding page (hidden from menu but accessible)
        add_submenu_page(
            null, // Hidden from menu
            __('Setup', 'commerce-studio'),
            __('Setup', 'commerce-studio'),
            'manage_options',
            'commerce-studio-onboarding',
            [$this, 'render_onboarding_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'commerce-studio') === false) {
            return;
        }

        wp_enqueue_style('cs-admin', CS_PLUGIN_URL . 'admin/css/admin.css', [], CS_VERSION);
        wp_enqueue_script('cs-admin', CS_PLUGIN_URL . 'admin/js/admin.js', ['jquery'], CS_VERSION, true);

        // Google Material Icons
        wp_enqueue_style('material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons', [], null);

        wp_localize_script('cs-admin', 'commerceStudio', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cs_admin_nonce'),
            'apiUrl' => CS_Config::get_api_url(),
            'storeUrl' => CS_Config::get_store_url(),
            'isOnboarded' => get_option('cs_onboarding_complete', false),
            'installedApps' => CS_Apps::get_installed_apps(),
            'availableApps' => CS_Apps::get_available_apps(),
            'appConfigs' => get_option('cs_app_configs', [])
        ]);
    }

    /**
     * Enqueue frontend assets for widgets
     */
    /**
     * Chat-based app slugs that should load on all pages (floating widgets).
     */
    private static $sitewide_widgets = [
        'conversational-ai', 'ai-sales-agent', 'avatar-2d', 'avatar-3d',
        'b1000000-0000-0000-0000-000000000005',
        'b2000000-0000-0000-0000-000000000010',
        'b2000000-0000-0000-0000-000000000001',
        'b2000000-0000-0000-0000-000000000002',
    ];

    public function enqueue_frontend_assets() {
        $installed_apps = CS_Apps::get_installed_apps();
        if (empty($installed_apps)) {
            return;
        }

        $is_product_page = is_product();
        $api_url = CS_Config::get_api_url();
        $widget_map = self::get_widget_file_map();
        $configs = get_option('cs_app_configs', []);
        $first_handle = null;

        // Load per-widget scripts from the connector server
        foreach ($installed_apps as $app_id) {
            $widget_file = $widget_map[$app_id] ?? null;
            if (!$widget_file) {
                continue;
            }

            // Non-chat widgets only load on product pages
            $is_sitewide = in_array($app_id, self::$sitewide_widgets, true);
            if (!$is_product_page && !$is_sitewide) {
                continue;
            }

            $handle = 'cs-widget-' . sanitize_title($app_id);
            $site_url = rawurlencode(CS_Config::get_store_url());
            $product_id = $is_product_page ? get_the_ID() : 0;
            $script_url = $api_url . '/api/widgets/' . $widget_file . '?site=' . $site_url . '&product=' . intval($product_id);

            wp_enqueue_script($handle, $script_url, [], CS_VERSION, true);

            if (!$first_handle) {
                $first_handle = $handle;
            }
        }

        // Attach global config to the first widget script
        if ($first_handle) {
            wp_localize_script($first_handle, 'commerceStudioConfig', [
                'apiUrl' => $api_url,
                'widgetUrl' => CS_Config::get_widget_url(),
                'storeId' => CS_Config::get_store_url(),
                'apiKey' => get_option('cs_api_key', ''),
                'environment' => CS_Config::get_environment(),
                'installedApps' => $installed_apps,
                'productId' => $is_product_page ? get_the_ID() : 0,
                'appConfigs' => $configs
            ]);
        }
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('cs_settings', 'cs_environment');
        register_setting('cs_settings', 'cs_api_key');
        register_setting('cs_settings', 'cs_store_id');
        register_setting('cs_settings', 'cs_tenant_id');
        register_setting('cs_settings', 'cs_data_region'); // EU or US - for multi-region sync routing
        register_setting('cs_settings', 'cs_store_url_override', ['sanitize_callback' => 'sanitize_url']); // Manual public URL override for managed hosting
        register_setting('cs_settings', 'cs_onboarding_complete');
        register_setting('cs_settings', 'cs_installed_apps');
    }

    /**
     * Get OAuth authorization URL for Partner Portal
     * This redirects to Partner Portal login, which then redirects back with API key
     */
    public static function get_oauth_url() {
        $callback_url = admin_url('admin.php?page=commerce-studio-onboarding&cs_oauth_callback=1');
        $state = wp_create_nonce('cs_oauth_state');

        $oauth_url = self::PARTNER_PORTAL_URL . '/partner/authorize?' . http_build_query([
            'platform' => 'woocommerce',
            'callback' => $callback_url,
            'state' => $state
        ]);

        return $oauth_url;
    }

    /**
     * Handle OAuth callback from Partner Portal
     * The Partner Portal redirects here with api_key, tenant_id, and partner_name
     */
    public function handle_oauth_callback() {
        // Check if this is an OAuth callback
        if (!isset($_GET['cs_oauth_callback']) || $_GET['cs_oauth_callback'] !== '1') {
            return;
        }

        // Verify state (CSRF protection)
        $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
        if (!wp_verify_nonce($state, 'cs_oauth_state')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Commerce Studio: Invalid OAuth state. Please try connecting again.</p></div>';
            });
            return;
        }

        // Get the API key and other data from callback
        $api_key = isset($_GET['api_key']) ? sanitize_text_field($_GET['api_key']) : '';
        $tenant_id = isset($_GET['tenant_id']) ? sanitize_text_field($_GET['tenant_id']) : '';
        $partner_name = isset($_GET['partner_name']) ? sanitize_text_field($_GET['partner_name']) : '';
        $platform = isset($_GET['platform']) ? sanitize_text_field($_GET['platform']) : '';

        if (empty($api_key) || empty($tenant_id)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Commerce Studio: Missing API key or tenant ID. Please try connecting again.</p></div>';
            });
            return;
        }

        // Validate API key format (cs_ prefix)
        if (strpos($api_key, 'cs_') !== 0) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Commerce Studio: Invalid API key format. Please try connecting again.</p></div>';
            });
            return;
        }

        // Save the API key and tenant ID
        update_option('cs_api_key', $api_key);
        update_option('cs_tenant_id', $tenant_id);
        update_option('cs_store_id', CS_Config::get_store_url());
        update_option('cs_partner_name', $partner_name);
        update_option('cs_oauth_connected', true);
        update_option('cs_oauth_connected_at', current_time('mysql'));

        // Clear endpoint cache to fetch fresh endpoints for this tenant
        CS_Config::refresh_endpoints();

        // Auto-sync products after OAuth connection
        // Schedule this as background task to not block the redirect
        wp_schedule_single_event(time() + 5, 'cs_auto_sync_products_after_oauth');

        // Redirect to onboarding page with success flag
        wp_redirect(admin_url('admin.php?page=commerce-studio-onboarding&oauth_success=1'));
        exit;
    }

    /**
     * Handle deep-link app installation from Commerce Studio.
     * When a user installs an app in Commerce Studio with a connected WooCommerce store,
     * the dashboard opens a new tab to: ?page=commerce-studio&cs_install={appSlug}
     * This method detects that param, installs the app locally, notifies the backend,
     * and sets a transient so the admin page can show a success banner.
     */
    public function handle_deep_link_install() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'commerce-studio') {
            return;
        }
        if (empty($_GET['cs_install'])) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $app_slug = sanitize_text_field($_GET['cs_install']);

        // Install the app locally
        CS_Apps::install_app($app_slug);

        // Notify Commerce Studio backend
        $response = wp_remote_post(CS_Config::get_api_url() . '/api/widgets/' . $app_slug . '/install', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'X-Store-URL'   => CS_Config::get_store_url(),
                'X-CS-API-Key'  => get_option('cs_api_key', ''),
                'X-Tenant-ID'   => get_option('cs_tenant_id', ''),
                'X-Data-Region' => get_option('cs_data_region', 'US')
            ],
            'body' => json_encode([
                'store_url'   => CS_Config::get_store_url(),
                'platform'    => 'woocommerce',
                'tenant_id'   => get_option('cs_tenant_id', ''),
                'data_region' => get_option('cs_data_region', 'US'),
                'source'      => 'deep_link'
            ]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            error_log('Commerce Studio: Deep-link install notify failed for ' . $app_slug . ': ' . $response->get_error_message());
        }

        // Store a transient so the admin page can render a success banner
        set_transient('cs_deep_link_installed', $app_slug, 60);

        error_log('Commerce Studio: Deep-link installed app ' . $app_slug);
    }

    /**
     * Render Home/Dashboard page
     */
    public function render_home_page() {
        // Check for deep-link install banner
        $deep_link_app = get_transient('cs_deep_link_installed');
        if ($deep_link_app) {
            delete_transient('cs_deep_link_installed');
            $dashboard_url = CS_Config::get_dashboard_url();
            $settings_url = $dashboard_url . '/dashboard/apps/' . esc_attr($deep_link_app) . '/settings';
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo '<strong>' . esc_html(ucwords(str_replace('-', ' ', $deep_link_app))) . '</strong> has been activated on your store. ';
            echo '<a href="' . esc_url($settings_url) . '" target="_blank">Return to Commerce Studio to configure &rarr;</a>';
            echo '</p></div>';
        }
        include CS_PLUGIN_DIR . 'admin/views/home.php';
    }

    /**
     * Render Apps page
     */
    public function render_apps_page() {
        include CS_PLUGIN_DIR . 'admin/views/apps.php';
    }

    /**
     * Render Settings page
     */
    public function render_settings_page() {
        include CS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Dynamically register WooCommerce hooks for each installed widget
     * based on its saved position config
     */
    public function setup_widget_hooks() {
        if (!is_product()) {
            return;
        }

        $installed_apps = CS_Apps::get_installed_apps();
        if (empty($installed_apps)) {
            return;
        }

        $configs = get_option('cs_app_configs', []);

        // Group widgets by their target position/hook
        $position_groups = [];
        foreach ($installed_apps as $app_id) {
            $config = $configs[$app_id] ?? [];
            $position = $config['position'] ?? 'before-cart';
            $position_groups[$position][] = $app_id;
        }

        // Register a WooCommerce hook for each position group
        foreach ($position_groups as $position => $apps) {
            list($hook, $priority) = self::get_wc_hook_for_position($position);
            add_action($hook, function() use ($apps, $configs) {
                $this->render_widget_group($apps, $configs);
            }, $priority);
        }
    }

    /**
     * Render a group of widgets that share the same position
     */
    public function render_widget_group($apps, $configs) {
        $widget_map = self::get_widget_file_map();
        $product_id = get_the_ID();

        foreach ($apps as $app_id) {
            $config = $configs[$app_id] ?? [];
            $widget_file = $widget_map[$app_id] ?? null;

            echo sprintf(
                '<div class="cs-widget" data-widget="%s" data-widget-file="%s" data-product-id="%d" data-display-mode="%s"></div>',
                esc_attr($app_id),
                esc_attr($widget_file ?? $app_id),
                intval($product_id),
                esc_attr($config['displayMode'] ?? 'modal')
            );
        }
    }

    /**
     * Render frontend widgets on product pages (legacy fallback)
     */
    public function render_widgets() {
        $installed_apps = CS_Apps::get_installed_apps();
        if (empty($installed_apps)) {
            return;
        }

        $configs = get_option('cs_app_configs', []);
        $this->render_widget_group($installed_apps, $configs);
    }

    /**
     * Shortcode handler
     */
    public function shortcode_handler($atts) {
        $atts = shortcode_atts([
            'app' => 'virtual-try-on',
            'product_id' => null
        ], $atts);

        $product_id = $atts['product_id'] ?: (is_product() ? get_the_ID() : null);

        return sprintf(
            '<div class="cs-widget" data-widget="%s" data-product-id="%s"></div>',
            esc_attr($atts['app']),
            esc_attr($product_id)
        );
    }

    /**
     * AJAX: Install app
     */
    public function ajax_install_app() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $app_id = sanitize_text_field($_POST['app_id'] ?? '');
        if (empty($app_id)) {
            wp_send_json_error(['message' => 'Invalid app ID']);
        }

        // Register with Commerce Studio API for billing tracking
        $response = wp_remote_post(CS_Config::get_api_url() . '/api/widgets/' . $app_id . '/install', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Store-URL' => CS_Config::get_store_url(),
                'X-CS-API-Key' => get_option('cs_api_key', ''),
                'X-Tenant-ID' => get_option('cs_tenant_id', ''),
                'X-Data-Region' => get_option('cs_data_region', 'US')
            ],
            'body' => json_encode([
                'store_url' => CS_Config::get_store_url(),
                'platform' => 'woocommerce',
                'tenant_id' => get_option('cs_tenant_id', ''),
                'data_region' => get_option('cs_data_region', 'US')
            ]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            error_log('Commerce Studio: Failed to notify install for ' . $app_id . ': ' . $response->get_error_message());
        }

        CS_Apps::install_app($app_id);

        wp_send_json_success([
            'message' => 'App installed successfully',
            'app_id' => $app_id,
            'installed_apps' => CS_Apps::get_installed_apps()
        ]);
    }

    /**
     * AJAX: Uninstall app
     * CRITICAL: Notify Commerce Studio for billing tracking
     */
    public function ajax_uninstall_app() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $app_id = sanitize_text_field($_POST['app_id'] ?? '');
        if (empty($app_id)) {
            wp_send_json_error(['message' => 'Invalid app ID']);
        }

        // Notify Commerce Studio API for billing tracking
        $response = wp_remote_post(CS_Config::get_api_url() . '/api/widgets/' . $app_id . '/uninstall', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Store-URL' => CS_Config::get_store_url(),
                'X-CS-API-Key' => get_option('cs_api_key', ''),
                'X-Tenant-ID' => get_option('cs_tenant_id', ''),
                'X-Data-Region' => get_option('cs_data_region', 'US')
            ],
            'body' => json_encode([
                'store_url' => CS_Config::get_store_url(),
                'platform' => 'woocommerce',
                'tenant_id' => get_option('cs_tenant_id', ''),
                'data_region' => get_option('cs_data_region', 'US')
            ]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            error_log('Commerce Studio: Failed to notify uninstall for ' . $app_id . ': ' . $response->get_error_message());
        }

        CS_Apps::uninstall_app($app_id);

        wp_send_json_success([
            'message' => 'App uninstalled',
            'installed_apps' => CS_Apps::get_installed_apps()
        ]);
    }

    /**
     * AJAX: Save app configuration
     */
    public function ajax_save_app_config() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $app_id = sanitize_text_field($_POST['app_id'] ?? '');
        $config_json = stripslashes($_POST['config'] ?? '{}');
        $config = json_decode($config_json, true);

        if (empty($app_id)) {
            wp_send_json_error(['message' => 'Invalid app ID']);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid configuration data']);
        }

        // Sanitize configuration values
        $sanitized_config = [
            'enabled' => !empty($config['enabled']),
            'position' => sanitize_text_field($config['position'] ?? 'before-cart'),
            'buttonText' => sanitize_text_field($config['buttonText'] ?? ''),
            'displayMode' => sanitize_text_field($config['displayMode'] ?? 'modal'),
            'categories' => sanitize_text_field($config['categories'] ?? ''),
            'units' => sanitize_text_field($config['units'] ?? 'mm'),
            'showGuidance' => !empty($config['showGuidance']),
            'saveResult' => !empty($config['saveResult']),
            'showRecommendations' => !empty($config['showRecommendations']),
            'confidenceThreshold' => floatval($config['confidenceThreshold'] ?? 0.8),
            'autoRotate' => !empty($config['autoRotate']),
            'enableZoom' => !empty($config['enableZoom']),
            'quality' => sanitize_text_field($config['quality'] ?? 'high'),
            'customClass' => sanitize_html_class($config['customClass'] ?? ''),
            // Button styling fields
            'buttonColor' => sanitize_hex_color($config['buttonColor'] ?? '#5c6ac4'),
            'buttonTextColor' => sanitize_hex_color($config['buttonTextColor'] ?? '#ffffff'),
            'buttonShape' => sanitize_text_field($config['buttonShape'] ?? 'rounded'),
            'buttonSize' => sanitize_text_field($config['buttonSize'] ?? 'medium'),
            'updated_at' => current_time('mysql')
        ];

        // Save to WordPress options
        $all_configs = get_option('cs_app_configs', []);
        $all_configs[$app_id] = $sanitized_config;
        update_option('cs_app_configs', $all_configs);

        // Sync with Commerce Studio API
        $response = wp_remote_post(CS_Config::get_api_url() . '/api/widgets/' . $app_id . '/config', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Store-URL' => CS_Config::get_store_url(),
                'X-API-Key' => get_option('cs_api_key', '')
            ],
            'body' => json_encode([
                'store_url' => CS_Config::get_store_url(),
                'platform' => 'woocommerce',
                'config' => $sanitized_config
            ]),
            'timeout' => 15
        ]);

        wp_send_json_success([
            'message' => 'Configuration saved successfully',
            'app_id' => $app_id,
            'config' => $sanitized_config
        ]);
    }

    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        $response = wp_remote_get(CS_Config::get_api_url() . '/health', ['timeout' => 10]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (wp_remote_retrieve_response_code($response) === 200) {
            wp_send_json_success([
                'message' => 'Connected to Commerce Studio (' . CS_Config::get_environment() . ')',
                'status' => $body
            ]);
        } else {
            wp_send_json_error(['message' => 'Connection failed']);
        }
    }

    /**
     * AJAX: Complete onboarding
     */
    public function ajax_complete_onboarding() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        update_option('cs_onboarding_complete', true);
        wp_send_json_success(['message' => 'Onboarding complete']);
    }

    /**
     * AJAX: Refresh apps from marketplace
     */
    public function ajax_refresh_apps() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $apps = CS_Apps::refresh_apps();

        wp_send_json_success([
            'message' => 'Apps refreshed from marketplace',
            'apps' => $apps
        ]);
    }

    /**
     * Render Onboarding page
     */
    public function render_onboarding_page() {
        include CS_PLUGIN_DIR . 'admin/views/onboarding.php';
    }

    /**
     * AJAX: Create new account
     */
    public function ajax_create_account() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $store_url = sanitize_url($_POST['store_url'] ?? CS_Config::get_store_url());
        $store_name = get_bloginfo('name');

        if (empty($email) || empty($password)) {
            wp_send_json_error(['message' => 'Email and password required']);
        }

        // Get API key for authentication
        $api_key = get_option('cs_api_key', '');

        // Register with Commerce Studio API - use correct endpoint
        $response = wp_remote_post(CS_Config::get_api_url() . '/integrations/woocommerce/register', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Commerce-Studio-Key' => $api_key
            ],
            'body' => json_encode([
                'storeUrl' => $store_url,
                'storeName' => $store_name,
                'consumerKey' => get_option('cs_consumer_key', ''),
                'consumerSecret' => get_option('cs_consumer_secret', ''),
                'userEmail' => $email,
                'userPassword' => $password
            ]),
            'timeout' => 45
        ]);

        // Check for errors
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 200 && $status_code < 300 && !empty($body['success'])) {
            // Save tenant info
            $tenant_id = $body['tenant']['id'] ?? '';
            $user_email = $body['user']['email'] ?? $email;
            $api_key = $body['apiKey'] ?? $body['api_key'] ?? '';
            $data_region = $body['dataRegion'] ?? 'US'; // EU or US for multi-region routing

            update_option('cs_tenant_id', $tenant_id);
            update_option('cs_account_email', $user_email);
            update_option('cs_store_id', $store_url);
            update_option('cs_data_region', $data_region);

            // Save API key if returned (auto-configured on signup)
            if (!empty($api_key)) {
                update_option('cs_api_key', $api_key);
                // Refresh endpoints for this tenant
                CS_Config::refresh_endpoints();

                // Generate WC REST API keys and register with connector
                // so the connector can pull orders/customers from this store
                $this->register_store_credentials_with_connector();

                // Auto-sync products after successful account creation
                $sync_result = $this->sync_products_to_commerce_studio();
                error_log('Commerce Studio: Auto-sync after account creation - ' .
                    ($sync_result['success'] ? 'Success: ' . ($sync_result['synced'] ?? 0) . ' products' : 'Failed: ' . $sync_result['message']));
            }

            wp_send_json_success([
                'message' => 'Account created successfully!',
                'email' => $user_email,
                'tenantId' => $tenant_id,
                'dataRegion' => $data_region,
                'productsSynced' => isset($sync_result) && $sync_result['success'] ? ($sync_result['synced'] ?? 0) : 0
            ]);
        } else {
            $error_message = $body['message'] ?? $body['error'] ?? 'Failed to create account';
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * AJAX: Connect existing account
     */
    public function ajax_connect_account() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $store_url = sanitize_url($_POST['store_url'] ?? CS_Config::get_store_url());
        $store_name = get_bloginfo('name');

        if (empty($email) || empty($password)) {
            wp_send_json_error(['message' => 'Email and password required']);
        }

        // Get API key for authentication
        $api_key = get_option('cs_api_key', '');

        // Connect existing account - uses same registration endpoint with user credentials
        $response = wp_remote_post(CS_Config::get_api_url() . '/integrations/woocommerce/register', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Commerce-Studio-Key' => $api_key
            ],
            'body' => json_encode([
                'storeUrl' => $store_url,
                'storeName' => $store_name,
                'consumerKey' => get_option('cs_consumer_key', ''),
                'consumerSecret' => get_option('cs_consumer_secret', ''),
                'userEmail' => $email,
                'userPassword' => $password
            ]),
            'timeout' => 45
        ]);

        // Check for errors
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 200 && $status_code < 300 && !empty($body['success'])) {
            // Save tenant info
            $tenant_id = $body['tenant']['id'] ?? '';
            $user_email = $body['user']['email'] ?? $email;
            $api_key = $body['apiKey'] ?? $body['api_key'] ?? '';
            $data_region = $body['dataRegion'] ?? 'US'; // EU or US for multi-region routing

            update_option('cs_tenant_id', $tenant_id);
            update_option('cs_account_email', $user_email);
            update_option('cs_store_id', $store_url);
            update_option('cs_data_region', $data_region);

            // Save API key if returned (auto-configured on login)
            if (!empty($api_key)) {
                update_option('cs_api_key', $api_key);
                // Refresh endpoints for this tenant
                CS_Config::refresh_endpoints();

                // Generate WC REST API keys and register with connector
                // so the connector can pull orders/customers from this store
                $this->register_store_credentials_with_connector();

                // Auto-sync products after successful account connection
                $sync_result = $this->sync_products_to_commerce_studio();
                error_log('Commerce Studio: Auto-sync after account connection - ' .
                    ($sync_result['success'] ? 'Success: ' . ($sync_result['synced'] ?? 0) . ' products' : 'Failed: ' . $sync_result['message']));
            }

            wp_send_json_success([
                'message' => 'Account connected successfully!',
                'email' => $user_email,
                'tenantId' => $tenant_id,
                'dataRegion' => $data_region,
                'productsSynced' => isset($sync_result) && $sync_result['success'] ? ($sync_result['synced'] ?? 0) : 0
            ]);
        } else {
            $error_message = $body['message'] ?? $body['error'] ?? 'Invalid email or password';
            wp_send_json_error(['message' => $error_message]);
        }
    }

    /**
     * Show an admin notice when the WordPress site URL looks like a managed-hosting
     * internal hostname (e.g. myftpupload.com) and no override has been set.
     */
    public function maybe_show_internal_hostname_notice() {
        // Only on Commerce Studio pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'commerce-studio') === false) {
            return;
        }

        // Skip if an override is already set
        if (!empty(get_option('cs_store_url_override', ''))) {
            return;
        }

        if (CS_Config::has_internal_hostname()) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo '<strong>Commerce Studio:</strong> Your WordPress site URL (<code>' . esc_html(get_site_url()) . '</code>) appears to be an internal hosting URL. ';
            echo 'Set your public store URL in <a href="' . esc_url(admin_url('admin.php?page=commerce-studio-settings')) . '">Settings</a> to ensure correct operation.';
            echo '</p></div>';
        }
    }

    /**
     * On plugin upgrade, ensure WC REST API credentials are registered with the connector.
     * Fixes stores that connected before credential registration was added.
     */
    public function maybe_register_credentials_on_upgrade() {
        $api_key = get_option('cs_api_key', '');
        if (empty($api_key)) {
            return; // Not connected yet
        }

        $registered_version = get_option('cs_credentials_registered_version', '');
        if ($registered_version === CS_VERSION) {
            return; // Already registered for this version
        }

        $this->register_store_credentials_with_connector();
        update_option('cs_credentials_registered_version', CS_VERSION);
    }

    /**
     * Generate WooCommerce REST API keys for Commerce Studio.
     * Creates a key pair in the woocommerce_api_keys table and stores
     * the unhashed consumer_key and consumer_secret in wp_options so the
     * connector can pull orders/customers from this store.
     *
     * @return array{consumer_key: string, consumer_secret: string}|null Keys or null on failure
     */
    private function generate_wc_api_keys() {
        if (!function_exists('wc_rand_hash') || !function_exists('wc_api_hash')) {
            error_log('Commerce Studio: WooCommerce API functions not available');
            return null;
        }

        // Reuse existing keys if already generated
        $existing_key = get_option('cs_consumer_key', '');
        $existing_secret = get_option('cs_consumer_secret', '');
        if (!empty($existing_key) && !empty($existing_secret)) {
            return [
                'consumer_key'    => $existing_key,
                'consumer_secret' => $existing_secret,
            ];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_api_keys';

        $consumer_key    = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();

        $wpdb->insert($table, [
            'user_id'         => get_current_user_id(),
            'description'     => 'Commerce Studio (auto-generated)',
            'permissions'     => 'read_write',
            'consumer_key'    => wc_api_hash($consumer_key),
            'consumer_secret' => $consumer_secret,
            'truncated_key'   => substr($consumer_key, -7),
        ]);

        if ($wpdb->insert_id) {
            update_option('cs_consumer_key', $consumer_key);
            update_option('cs_consumer_secret', $consumer_secret);
            error_log('Commerce Studio: Generated WC REST API keys (id=' . $wpdb->insert_id . ')');
            return [
                'consumer_key'    => $consumer_key,
                'consumer_secret' => $consumer_secret,
            ];
        }

        error_log('Commerce Studio: Failed to generate WC REST API keys - ' . $wpdb->last_error);
        return null;
    }

    /**
     * Register this store's WooCommerce REST API credentials with the connector.
     * The connector stores them in woocommerce_stores so it can pull
     * orders/customers on behalf of the store during scheduled syncs.
     */
    private function register_store_credentials_with_connector() {
        $keys = $this->generate_wc_api_keys();
        if (!$keys) {
            return;
        }

        $api_url = CS_Config::get_api_url();
        $store_url = CS_Config::get_store_url();

        $response = wp_remote_post($api_url . '/api/connect', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'store_url'       => $store_url,
                'consumer_key'    => $keys['consumer_key'],
                'consumer_secret' => $keys['consumer_secret'],
            ]),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('Commerce Studio: Failed to register store credentials with connector - ' . $response->get_error_message());
            return;
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status >= 200 && $status < 300) {
            error_log('Commerce Studio: Store credentials registered with connector');
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            error_log('Commerce Studio: Connector credential registration failed (' . $status . '): ' . ($body['error'] ?? 'unknown'));
        }
    }

    /**
     * AJAX: Sync products
     * Fetches products from WooCommerce and pushes them to Commerce Studio
     */
    public function ajax_sync_products() {
        // Manual nonce verification with proper JSON error response
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (empty($nonce) || !wp_verify_nonce($nonce, 'cs_admin_nonce')) {
            error_log('Commerce Studio: Sync products - nonce verification failed');
            wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.'], 403);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized. Admin access required.'], 403);
            return;
        }

        // Check if WooCommerce is active
        if (!function_exists('wc_get_products')) {
            wp_send_json_error(['message' => 'WooCommerce is not active. Please activate WooCommerce first.']);
            return;
        }

        try {
            $result = $this->sync_products_to_commerce_studio();

            if ($result['success']) {
                wp_send_json_success([
                    'message' => $result['message'],
                    'synced' => $result['synced'],
                    'total' => $result['total']
                ]);
            } else {
                wp_send_json_error(['message' => $result['message']]);
            }
        } catch (Exception $e) {
            error_log('Commerce Studio: Sync products exception - ' . $e->getMessage());
            wp_send_json_error(['message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Sync WooCommerce products to Commerce Studio
     * This fetches all published products and pushes them to the Commerce Studio API
     *
     * @param int $limit Maximum number of products to sync (0 = all)
     * @return array Result with success status and counts
     */
    public function sync_products_to_commerce_studio($limit = 0) {
        $api_key = get_option('cs_api_key', '');
        $tenant_id = get_option('cs_tenant_id', '');
        $data_region = get_option('cs_data_region', 'US'); // EU or US for multi-region routing

        if (empty($api_key) || empty($tenant_id)) {
            return [
                'success' => false,
                'message' => 'Not connected to Commerce Studio. Please complete onboarding first.'
            ];
        }

        // Determine sync type based on last sync timestamp
        $last_sync = get_option('cs_last_product_sync', '');
        $force_full = empty($last_sync) || (time() - strtotime($last_sync)) > 604800; // 7 days
        $sync_type = $force_full ? 'full' : 'incremental';

        // Get WooCommerce products
        $args = [
            'status' => 'publish',
            'limit' => $limit > 0 ? $limit : -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        // For incremental sync, only fetch products modified since last sync
        // 5-minute safety buffer to catch edge cases
        if (!$force_full && $last_sync) {
            $args['date_modified'] = '>' . wp_date('Y-m-d H:i:s', strtotime($last_sync) - 300);
        }

        $products = wc_get_products($args);

        if (empty($products)) {
            return [
                'success' => true,
                'message' => 'No products found to sync',
                'synced' => 0,
                'total' => 0
            ];
        }

        // Format products for Commerce Studio API
        $formatted_products = [];
        foreach ($products as $product) {
            $formatted_products[] = $this->format_product_for_sync($product);
        }

        // Push to Commerce Studio API via connector (handles auth and forwarding)
        $api_url = CS_Config::get_api_url(); // Use connector, not backend directly
        error_log('Commerce Studio: Syncing products to ' . $data_region . ' region via connector');
        $response = wp_remote_post($api_url . '/api/commerce-studio/sync-products', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-CS-API-Key' => $api_key, // Connector expects X-CS-API-Key header
                'X-Tenant-ID' => $tenant_id,
                'X-Data-Region' => $data_region // Route to correct regional backend (EU/US)
            ],
            'body' => json_encode([
                'platform' => 'woocommerce',
                'store_url' => CS_Config::get_store_url(),
                'shop' => parse_url(CS_Config::get_store_url(), PHP_URL_HOST),
                'products' => $formatted_products,
                'sync_mode' => 'upsert',
                'sync_type' => $sync_type,
                'last_sync_at' => $last_sync ?: null,
                'data_region' => $data_region
            ]),
            'timeout' => 120
        ]);

        if (is_wp_error($response)) {
            error_log('Commerce Studio: Product sync failed - ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code >= 200 && $status_code < 300) {
            $synced = $body['synced'] ?? $body['created'] ?? count($formatted_products);

            // Save sync timestamp from backend response (or fall back to current time)
            $sync_timestamp = $body['sync_completed_at'] ?? current_time('mysql');
            update_option('cs_last_product_sync', $sync_timestamp);
            update_option('cs_products_synced_count', $synced);

            error_log('Commerce Studio: Product sync complete - ' . $synced . ' products synced');

            return [
                'success' => true,
                'message' => 'Successfully synced ' . $synced . ' products to Commerce Studio',
                'synced' => $synced,
                'total' => count($formatted_products)
            ];
        } else {
            $error_message = $body['message'] ?? $body['error'] ?? 'Unknown error';
            error_log('Commerce Studio: Product sync failed - ' . $error_message);
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $error_message
            ];
        }
    }

    /**
     * AJAX: Sync orders and customers
     * Triggers the connector to fetch orders and customers from WooCommerce
     */
    public function ajax_sync_orders_customers() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (empty($nonce) || !wp_verify_nonce($nonce, 'cs_admin_nonce')) {
            error_log('Commerce Studio: Sync orders/customers - nonce verification failed');
            wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.'], 403);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized. Admin access required.'], 403);
            return;
        }

        if (!class_exists('WooCommerce')) {
            wp_send_json_error(['message' => 'WooCommerce is not active. Please activate WooCommerce first.']);
            return;
        }

        try {
            $result = $this->sync_orders_customers_to_commerce_studio();

            if ($result['success']) {
                wp_send_json_success([
                    'message' => $result['message'],
                    'orders_synced' => $result['orders_synced'],
                    'customers_synced' => $result['customers_synced']
                ]);
            } else {
                wp_send_json_error(['message' => $result['message']]);
            }
        } catch (Exception $e) {
            error_log('Commerce Studio: Sync orders/customers exception - ' . $e->getMessage());
            wp_send_json_error(['message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Sync WooCommerce orders and customers to Commerce Studio
     * Tells the connector to pull orders and customers from this store
     *
     * @return array Result with success status and counts
     */
    public function sync_orders_customers_to_commerce_studio() {
        $api_key = get_option('cs_api_key', '');
        $tenant_id = get_option('cs_tenant_id', '');
        $data_region = get_option('cs_data_region', 'US');

        if (empty($api_key) || empty($tenant_id)) {
            return [
                'success' => false,
                'message' => 'Not connected to Commerce Studio. Please complete onboarding first.'
            ];
        }

        $api_url = CS_Config::get_api_url();
        error_log('Commerce Studio: Syncing orders & customers to ' . $data_region . ' region via connector');

        $response = wp_remote_post($api_url . '/api/commerce-studio/sync-orders-customers', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-CS-API-Key' => $api_key,
                'X-Tenant-ID' => $tenant_id,
                'X-Data-Region' => $data_region
            ],
            'body' => json_encode([
                'store_url' => CS_Config::get_store_url(),
                'shop' => parse_url(CS_Config::get_store_url(), PHP_URL_HOST),
                'data_region' => $data_region
            ]),
            'timeout' => 120
        ]);

        if (is_wp_error($response)) {
            error_log('Commerce Studio: Orders/customers sync failed - ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code >= 200 && $status_code < 300) {
            $orders_synced = $body['orders']['synced'] ?? $body['orders_synced'] ?? 0;
            $customers_synced = $body['customers']['synced'] ?? $body['customers_synced'] ?? 0;

            update_option('cs_last_orders_customers_sync', current_time('mysql'));
            update_option('cs_orders_synced_count', $orders_synced);
            update_option('cs_customers_synced_count', $customers_synced);

            error_log('Commerce Studio: Orders/customers sync complete - ' . $orders_synced . ' orders, ' . $customers_synced . ' customers');

            return [
                'success' => true,
                'message' => 'Successfully synced ' . $orders_synced . ' orders and ' . $customers_synced . ' customers',
                'orders_synced' => $orders_synced,
                'customers_synced' => $customers_synced
            ];
        } else {
            $error_message = $body['message'] ?? $body['error'] ?? 'Unknown error';
            error_log('Commerce Studio: Orders/customers sync failed - ' . $error_message);
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $error_message
            ];
        }
    }

    /**
     * Format a WooCommerce product for Commerce Studio sync
     *
     * @param WC_Product $product
     * @return array Formatted product data
     */
    private function format_product_for_sync($product) {
        $image_url = wp_get_attachment_url($product->get_image_id());
        $gallery_ids = $product->get_gallery_image_ids();
        $gallery_urls = array_map('wp_get_attachment_url', $gallery_ids);

        // Get product categories
        $categories = [];
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = $term->name;
            }
        }

        // Get product attributes (useful for eyewear - frame color, size, etc.)
        $attributes = [];
        foreach ($product->get_attributes() as $attr_name => $attr) {
            if ($attr->is_taxonomy()) {
                $values = wc_get_product_terms($product->get_id(), $attr->get_name(), ['fields' => 'names']);
                $attributes[$attr_name] = implode(', ', $values);
            } else {
                $attributes[$attr_name] = $attr->get_options();
            }
        }

        // Check for GLB/3D model in product meta or attachments
        $glb_url = get_post_meta($product->get_id(), '_cs_glb_url', true);
        if (empty($glb_url)) {
            $glb_url = get_post_meta($product->get_id(), 'glb_url', true);
        }
        if (empty($glb_url)) {
            $glb_url = get_post_meta($product->get_id(), '_3d_model_url', true);
        }

        return [
            'external_id' => (string) $product->get_id(),
            'sku' => $product->get_sku() ?: 'WC-' . $product->get_id(),
            'name' => $product->get_name(),
            'description' => $product->get_short_description() ?: $product->get_description(),
            'price' => (float) $product->get_price(),
            'regular_price' => (float) $product->get_regular_price(),
            'sale_price' => $product->get_sale_price() ? (float) $product->get_sale_price() : null,
            'currency' => get_woocommerce_currency(),
            'image_url' => $image_url ?: null,
            'images' => array_filter(array_merge([$image_url], $gallery_urls)),
            'url' => $product->get_permalink(),
            'categories' => $categories,
            'tags' => wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names']),
            'brand' => get_post_meta($product->get_id(), '_brand', true) ?: null,
            'stock_quantity' => $product->get_stock_quantity(),
            'in_stock' => $product->is_in_stock(),
            'glb_url' => $glb_url ?: null,
            'attributes' => $attributes,
            'metadata' => [
                'woocommerce_id' => $product->get_id(),
                'product_type' => $product->get_type(),
                'weight' => $product->get_weight(),
                'dimensions' => [
                    'length' => $product->get_length(),
                    'width' => $product->get_width(),
                    'height' => $product->get_height()
                ]
            ]
        ];
    }

    /**
     * AJAX: Embed apps
     */
    public function ajax_embed_apps() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $apps = isset($_POST['apps']) ? array_map('sanitize_text_field', $_POST['apps']) : [];

        if (empty($apps)) {
            wp_send_json_error(['message' => 'No apps selected']);
        }

        // Install each app
        foreach ($apps as $app_id) {
            CS_Apps::install_app($app_id);
        }

        // Register with Commerce Studio API
        $response = wp_remote_post(CS_Config::get_api_url() . '/api/commerce-studio/embed-apps', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Store-URL' => CS_Config::get_store_url()
            ],
            'body' => json_encode([
                'store_url' => CS_Config::get_store_url(),
                'apps' => $apps,
                'platform' => 'woocommerce'
            ]),
            'timeout' => 15
        ]);

        wp_send_json_success([
            'message' => 'Apps installed',
            'installed_apps' => CS_Apps::get_installed_apps()
        ]);
    }
}

/**
 * Plugin Update Checker
 * Allows WordPress to check for updates from our server
 */
class CS_Update_Checker {
    const UPDATE_URL = 'https://commerce-studio-woocommerce-production-353252826752.us-central1.run.app/api/plugin/update-check';
    const CACHE_KEY = 'cs_update_check';
    const CACHE_DURATION = 3600; // 1 hour (reduced from 12 hours for faster update detection)

    public static function init() {
        add_filter('site_transient_update_plugins', [__CLASS__, 'check_for_updates']);
        add_filter('plugins_api', [__CLASS__, 'plugin_info'], 10, 3);
        add_action('upgrader_process_complete', [__CLASS__, 'clear_update_cache'], 10, 2);
        add_action('wp_ajax_cs_force_update_check', [__CLASS__, 'ajax_force_update_check']);
    }

    /**
     * Force check for updates (clears cache and re-checks)
     */
    public static function ajax_force_update_check() {
        check_ajax_referer('cs_admin_nonce', 'nonce');

        if (!current_user_can('update_plugins')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // Clear the cache
        delete_transient(self::CACHE_KEY);

        // Also clear WordPress plugin update cache
        delete_site_transient('update_plugins');

        // Get fresh update info
        $update_info = self::get_update_info();

        if ($update_info) {
            $has_update = version_compare(CS_VERSION, $update_info['version'], '<');
            wp_send_json_success([
                'current_version' => CS_VERSION,
                'latest_version' => $update_info['version'],
                'has_update' => $has_update,
                'message' => $has_update
                    ? sprintf('Update available! Version %s is ready to install.', $update_info['version'])
                    : 'You have the latest version.'
            ]);
        } else {
            wp_send_json_error(['message' => 'Could not check for updates. Please try again.']);
        }
    }

    /**
     * Check for plugin updates
     */
    public static function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get update info from our server
        $update_info = self::get_update_info();

        if ($update_info && version_compare(CS_VERSION, $update_info['version'], '<')) {
            $plugin_slug = CS_PLUGIN_BASENAME;

            $transient->response[$plugin_slug] = (object) [
                'id'            => 'commerce-studio/commerce-studio.php',
                'slug'          => 'commerce-studio',
                'plugin'        => $plugin_slug,
                'new_version'   => $update_info['version'],
                'url'           => $update_info['homepage'] ?? 'https://commerce-studio.com',
                'package'       => $update_info['download_url'] ?? '',
                'icons'         => [
                    '1x' => $update_info['icon'] ?? 'https://commerce.varai.ai/assets/icon-128.png',
                    '2x' => $update_info['icon_2x'] ?? 'https://commerce.varai.ai/assets/icon-256.png',
                ],
                'banners'       => [
                    'low'  => $update_info['banner'] ?? '',
                    'high' => $update_info['banner_2x'] ?? '',
                ],
                'tested'        => $update_info['tested_wp'] ?? '6.4',
                'requires_php'  => $update_info['requires_php'] ?? '7.4',
                'compatibility' => new \stdClass(),
            ];
        }

        return $transient;
    }

    /**
     * Plugin information for the "View Details" popup
     */
    public static function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== 'commerce-studio') {
            return $result;
        }

        $update_info = self::get_update_info();

        if (!$update_info) {
            return $result;
        }

        return (object) [
            'name'              => 'Commerce Studio',
            'slug'              => 'commerce-studio',
            'version'           => $update_info['version'],
            'author'            => '<a href="https://commerce-studio.com">Commerce Studio</a>',
            'author_profile'    => 'https://commerce-studio.com',
            'homepage'          => 'https://commerce-studio.com',
            'short_description' => 'AI-powered eyewear retail solutions for WooCommerce.',
            'sections'          => [
                'description'   => $update_info['description'] ?? 'Commerce Studio provides Virtual Try-On, PD Measurement, AI Recommendations, and more for your WooCommerce eyewear store.',
                'installation'  => $update_info['installation'] ?? 'Upload the plugin files to the `/wp-content/plugins/commerce-studio` directory, or install the plugin through the WordPress plugins screen directly.',
                'changelog'     => $update_info['changelog'] ?? '',
            ],
            'download_link'     => $update_info['download_url'] ?? '',
            'requires'          => $update_info['requires_wp'] ?? '5.8',
            'tested'            => $update_info['tested_wp'] ?? '6.4',
            'requires_php'      => $update_info['requires_php'] ?? '7.4',
            'last_updated'      => $update_info['last_updated'] ?? '',
            'banners'           => [
                'low'  => $update_info['banner'] ?? '',
                'high' => $update_info['banner_2x'] ?? '',
            ],
        ];
    }

    /**
     * Get update information from our server
     */
    private static function get_update_info() {
        // Check cache first
        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch from server
        $response = wp_remote_get(self::UPDATE_URL, [
            'headers' => [
                'Accept' => 'application/json',
                'X-Current-Version' => CS_VERSION,
                'X-Site-URL' => CS_Config::get_store_url(),
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['version'])) {
            set_transient(self::CACHE_KEY, $body, self::CACHE_DURATION);
            return $body;
        }

        return null;
    }

    /**
     * Clear update cache after plugin update
     */
    public static function clear_update_cache($upgrader, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            delete_transient(self::CACHE_KEY);
        }
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        Commerce_Studio::get_instance();
        CS_Update_Checker::init();
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Commerce Studio requires WooCommerce to be installed and activated.', 'commerce-studio');
            echo '</p></div>';
        });
    }
});

// Activation hook
register_activation_hook(__FILE__, function() {
    add_option('cs_environment', 'production'); // Default to production for live stores
    add_option('cs_onboarding_complete', false);
    add_option('cs_installed_apps', []);
    add_option('cs_sync_frequency', 'default');
    Commerce_Studio::schedule_sync_cron('default');
});

// Deactivation hook - clean up and notify backend
register_deactivation_hook(__FILE__, function() {
    // Clear scheduled sync cron events
    wp_clear_scheduled_hook('cs_scheduled_product_sync');
    wp_clear_scheduled_hook('cs_scheduled_orders_sync');

    $store_url = CS_Config::get_store_url();
    $api_key = get_option('cs_api_key', '');
    $installed_apps = get_option('cs_installed_apps', []);

    // Notify backend of plugin deactivation
    $api_url = get_option('cs_environment', 'production') === 'production'
        ? 'https://woocommerce.commerce-studio.com'
        : 'https://commerce-studio-woocommerce-staging-353252826752.us-central1.run.app';

    // Call backend to handle disconnection and app cleanup
    wp_remote_post($api_url . '/api/platform/disconnect', [
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Store-URL' => $store_url,
            'X-API-Key' => $api_key
        ],
        'body' => json_encode([
            'store_url' => $store_url,
            'platform' => 'woocommerce',
            'installed_apps' => $installed_apps,
            'action' => 'deactivate',
            'reason' => 'plugin_deactivated'
        ]),
        'timeout' => 15,
        'blocking' => false // Don't wait for response
    ]);

    // Log deactivation locally
    error_log('Commerce Studio: Plugin deactivated for ' . $store_url);
});

// Uninstall is handled by uninstall.php for complete cleanup
