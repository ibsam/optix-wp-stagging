<?php
/**
 * Commerce Studio - Apps Page
 * Matches Magento app store UI with tab filtering
 */

if (!defined('WPINC')) {
    die;
}

$installed_apps = CS_Apps::get_installed_apps();
$available_apps = CS_Apps::get_available_apps();

// Category mappings for filtering (matches Magento implementation)
$category_groups = array(
    'eyewear' => array('engagement', 'vto', 'try-on', 'sizing', 'viewer', 'catalog'),
    'ai' => array('support', 'chat', 'avatar', 'recommendations', 'analytics', 'ai', 'search'),
    'retail' => array('retail', 'inventory', 'shipping', 'fulfillment', 'pos')
);
?>

<!-- Load Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<div class="wrap cs-admin">
    <div class="cs-header">
        <div class="cs-header-content">
            <a href="<?php echo admin_url('admin.php?page=commerce-studio'); ?>" class="cs-back-link">
                <span class="material-icons">arrow_back</span>
                Back
            </a>
            <h1>
                <span class="material-icons">apps</span>
                Commerce Studio Apps
            </h1>
            <p class="cs-subtitle">AI-powered tools for your eyewear store</p>
        </div>
    </div>

    <!-- Tab Filters -->
    <div class="cs-tabs">
        <button class="cs-tab active" data-filter="all">All Apps</button>
        <button class="cs-tab" data-filter="eyewear">Eyewear</button>
        <button class="cs-tab" data-filter="ai">AI Tools</button>
        <button class="cs-tab" data-filter="retail">Retail</button>
        <button class="cs-tab" data-filter="installed">Installed</button>
    </div>

    <div class="cs-apps-grid">
        <?php foreach ($available_apps as $app):
            $is_installed = in_array($app['slug'], $installed_apps) || in_array($app['id'], $installed_apps);
            $category = strtolower($app['category']);
            $category_class = 'cs-category-' . $category;

            // Determine filter groups
            $filter_groups = array('all');
            if ($is_installed) {
                $filter_groups[] = 'installed';
            }
            if (in_array($category, $category_groups['eyewear'])) {
                $filter_groups[] = 'eyewear';
            }
            if (in_array($category, $category_groups['ai'])) {
                $filter_groups[] = 'ai';
            }
            if (in_array($category, $category_groups['retail'])) {
                $filter_groups[] = 'retail';
            }
        ?>
        <?php
            // Determine tier badge styling
            $tier = $app['tier'] ?? 'core';
            $tier_badge_class = 'cs-badge-tier-' . $tier;
            $tier_label = '';
            if (!empty($app['badge'])) {
                $tier_label = $app['badge'];
            } elseif ($tier === 'premium') {
                $tier_label = 'Premium';
            } elseif ($tier === 'enterprise') {
                $tier_label = 'Enterprise';
            }

            // Check for contact sales
            $contact_sales = !empty($app['contactSales']);
            $price = $app['price'] ?? 'Included';

            // Rating display
            $rating = $app['rating'] ?? null;
        ?>
        <div class="cs-app-card <?php echo $is_installed ? 'installed' : ''; ?> <?php echo $contact_sales ? 'cs-enterprise' : ''; ?>"
             data-app-id="<?php echo esc_attr($app['slug']); ?>"
             data-filter-groups="<?php echo esc_attr(implode(',', $filter_groups)); ?>"
             data-category="<?php echo esc_attr($category); ?>"
             data-tier="<?php echo esc_attr($tier); ?>">
            <div class="cs-app-header">
                <div class="cs-app-icon">
                    <span class="material-icons"><?php echo esc_html($app['icon']); ?></span>
                </div>
                <div class="cs-app-badges">
                    <?php if (!empty($tier_label)): ?>
                    <span class="cs-badge <?php echo esc_attr($tier_badge_class); ?>">
                        <?php echo esc_html($tier_label); ?>
                    </span>
                    <?php endif; ?>
                    <span class="cs-badge <?php echo esc_attr($category_class); ?>">
                        <?php echo esc_html(ucwords(str_replace('-', ' ', $app['category']))); ?>
                    </span>
                    <?php if ($is_installed): ?>
                    <span class="cs-badge cs-badge-success">Installed</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cs-app-body">
                <h3><?php echo esc_html($app['name']); ?></h3>
                <p><?php echo esc_html($app['description']); ?></p>

                <?php if (!empty($app['developer'])): ?>
                <div class="cs-app-developer">
                    <span class="material-icons">verified</span>
                    <span><?php echo esc_html($app['developer']); ?></span>
                    <?php if ($rating): ?>
                    <span class="cs-app-rating">
                        <span class="material-icons">star</span>
                        <?php echo esc_html($rating); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($app['features'])): ?>
                <div class="cs-app-features">
                    <?php foreach (array_slice($app['features'], 0, 3) as $feature): ?>
                    <span class="cs-feature">
                        <span class="material-icons">check</span>
                        <?php echo esc_html($feature); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="cs-app-pricing">
                    <span class="cs-price <?php echo $contact_sales ? 'cs-price-enterprise' : ''; ?>">
                        <?php echo esc_html($price); ?>
                    </span>
                </div>
            </div>

            <div class="cs-app-footer">
                <?php if ($is_installed): ?>
                <button type="button" class="button button-secondary cs-configure-app" data-app-id="<?php echo esc_attr($app['slug']); ?>">
                    <span class="material-icons">settings</span>
                    Configure
                </button>
                <button type="button" class="button cs-preview-app" data-app-id="<?php echo esc_attr($app['slug']); ?>">
                    <span class="material-icons">visibility</span>
                    Demo
                </button>
                <button type="button" class="button cs-faq-app" data-app-id="<?php echo esc_attr($app['slug']); ?>">
                    <span class="material-icons">help_outline</span>
                    FAQ
                </button>
                <?php elseif ($contact_sales): ?>
                <a href="mailto:sales@varai.ai?subject=<?php echo urlencode('Enterprise Inquiry: ' . $app['name']); ?>" class="button button-primary cs-contact-sales">
                    <span class="material-icons">email</span>
                    Contact Sales
                </a>
                <button type="button" class="button cs-preview-app" data-app-id="<?php echo esc_attr($app['slug']); ?>">
                    <span class="material-icons">visibility</span>
                    Demo
                </button>
                <button type="button" class="button cs-faq-app" data-app-id="<?php echo esc_attr($app['slug']); ?>">
                    <span class="material-icons">help_outline</span>
                    FAQ
                </button>
                <?php else: ?>
                <button type="button" class="button button-primary cs-install-app" data-app-id="<?php echo esc_attr($app['slug']); ?>">
                    <span class="material-icons">download</span>
                    Install
                </button>
                <button type="button" class="button cs-preview-app" data-app-id="<?php echo esc_attr($app['slug']); ?>">
                    <span class="material-icons">visibility</span>
                    Demo
                </button>
                <button type="button" class="button cs-faq-app" data-app-id="<?php echo esc_attr($app['slug']); ?>">
                    <span class="material-icons">help_outline</span>
                    FAQ
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty state for filtered results -->
    <div class="cs-empty-state cs-filter-empty" style="display: none;">
        <span class="material-icons">search_off</span>
        <h3>No apps found</h3>
        <p>No apps match the selected filter.</p>
    </div>

    <!-- Usage Instructions -->
    <div class="cs-card cs-usage-card">
        <div class="cs-card-header">
            <h2>
                <span class="material-icons">code</span>
                Using Apps on Your Store
            </h2>
        </div>
        <div class="cs-card-body">
            <p>Once installed, apps will automatically appear on your product pages. You can also use shortcodes for custom placement:</p>

            <div class="cs-code-examples">
                <div class="cs-code-example">
                    <h4>Virtual Try-On</h4>
                    <code>[commerce_studio app="virtual-try-on"]</code>
                </div>
                <div class="cs-code-example">
                    <h4>PD Measurement</h4>
                    <code>[commerce_studio app="pd-measurement"]</code>
                </div>
                <div class="cs-code-example">
                    <h4>AI Recommendations</h4>
                    <code>[commerce_studio app="ai-recommendations"]</code>
                </div>
                <div class="cs-code-example">
                    <h4>Lens Selection Journey</h4>
                    <code>[commerce_studio app="lens-journey"]</code>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Install Modal -->
<div id="cs-install-modal" class="cs-modal" style="display: none;">
    <div class="cs-modal-overlay"></div>
    <div class="cs-modal-content">
        <div class="cs-modal-header">
            <h2 id="cs-modal-title">Install App</h2>
            <button type="button" class="cs-modal-close">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="cs-modal-body">
            <div id="cs-modal-app-info"></div>

            <div class="cs-install-info">
                <h4>What happens when you install:</h4>
                <ul>
                    <li><span class="material-icons">check</span> Widget code will be injected into your storefront</li>
                    <li><span class="material-icons">check</span> App will be available on relevant product pages</li>
                    <li><span class="material-icons">check</span> Analytics tracking will begin</li>
                    <li><span class="material-icons">check</span> You can configure settings anytime</li>
                </ul>
            </div>
        </div>
        <div class="cs-modal-footer">
            <button type="button" class="button" id="cs-modal-cancel">Cancel</button>
            <button type="button" class="button button-primary" id="cs-modal-confirm">
                <span class="material-icons">download</span>
                Install App
            </button>
        </div>
    </div>
</div>
