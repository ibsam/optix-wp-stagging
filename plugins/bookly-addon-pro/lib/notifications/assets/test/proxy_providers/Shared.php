<?php
namespace BooklyPro\Lib\Notifications\Assets\Test\ProxyProviders;

use Bookly\Lib\Notifications\Assets\Test\Codes;
use Bookly\Lib\Notifications\Assets\Test\Proxy;
use Bookly\Lib\Utils;

abstract class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareReplaceCodes( array $replace_codes, Codes $codes, $format )
    {
        $replace_codes['gift_card'] = 'GIFT-test';
        $replace_codes['gift_card_amount'] = Utils\Price::format( 10 );
        $replace_codes['gift_card_image'] = 'https://dummyimage.com/100x60/666666/000000';
        if ( $format === 'html' ) {
            $replace_codes['gift_card_image'] = Utils\Common::getImageTag( $replace_codes['gift_card_image'], '' );
        }
        $replace_codes['gift_card_date_limit_to'] = Utils\DateTime::formatDate( date_create( 'next month' )->format( 'Y-m-d' ) );
        $replace_codes['gift_card_date_limit_from'] = Utils\DateTime::formatDate( date_create( 'next day' )->format( 'Y-m-d' ) );

        return $replace_codes;
    }
}