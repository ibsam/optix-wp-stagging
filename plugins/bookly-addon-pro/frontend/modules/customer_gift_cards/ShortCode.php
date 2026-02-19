<?php
namespace BooklyPro\Frontend\Modules\CustomerGiftCards;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use BooklyPro\Lib;
use Bookly\Backend\Modules;
use BooklyPro\Backend\Modules\Appearance;
use BooklyPro\Frontend\Modules\ModernBookingForm;

class ShortCode extends BooklyLib\Base\ShortCode
{
    public static $code = 'bookly-customer-gift-cards';

    /**
     * Link styles.
     */
    public static function linkStyles()
    {
        self::enqueueStyles( array(
            'bookly' => array(
                'backend/resources/tailwind/tailwind.css' => array(),
                'frontend/resources/css/bootstrap-icons.min.css' => array(),
            ),
        ) );
    }

    /**
     * Link scripts.
     */
    public static function linkScripts()
    {
        self::enqueueScripts( array(
            'module' => array(
                'js/customer-gift-cards.js' => array( 'bookly-frontend-globals' ),
            ),
        ) );

        wp_localize_script( 'bookly-customer-gift-cards.js', 'BooklyL10nCustomerGiftCards', array(
            'format_price' => BooklyLib\Utils\Price::formatOptions(),
            'moment_format_date' => BooklyLib\Utils\DateTime::convertFormat( 'date', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'l10n' => array(
                'copied' => __( 'Copied to clipboard', 'bookly' ),
            )
        ) );
    }

    /**
     * Render shortcode.
     *
     * @param array $attr
     * @return string
     */
    public static function render( $attr )
    {
        global $sitepress;

        // Disable caching.
        BooklyLib\Utils\Common::noCache();

        // Prepare URL for AJAX requests.
        $ajaxurl = admin_url( 'admin-ajax.php' );

        // Support WPML.
        if ( $sitepress instanceof \SitePress ) {
            $ajaxurl = add_query_arg( array( 'lang' => $sitepress->get_current_language() ), $ajaxurl );
        }

        $form_id = uniqid( 'bookly-customer-gift-cards-', false );

        return self::renderTemplate( 'short_code', compact( 'ajaxurl', 'form_id' ), false );
    }
}