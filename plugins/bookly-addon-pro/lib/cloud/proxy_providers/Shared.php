<?php
namespace BooklyPro\Lib\Cloud\ProxyProviders;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\Cloud\Square;
use BooklyPro\Lib\Cloud\Gift;
use BooklyPro\Backend\Modules;

class Shared extends BooklyLib\Cloud\Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function getProduct( $slug, $api )
    {
        switch ( $slug ) {
            case BooklyLib\Cloud\Account::PRODUCT_GIFT:
                return $api->gift = $api->gift ?: new Gift( $api );
            case BooklyLib\Cloud\Account::PRODUCT_SQUARE:
                return $api->square = $api->square ?: new Square( $api );
        }
    }

    /**
     * @inerhitDoc
     */
    public static function renderCloudMenu( array $product )
    {
        switch ( $product['id'] ) {
            case BooklyLib\Cloud\Account::PRODUCT_GIFT:
                Modules\CloudGiftCards\Page::addBooklyCloudMenuItem( $product );
                break;
        }
    }
}