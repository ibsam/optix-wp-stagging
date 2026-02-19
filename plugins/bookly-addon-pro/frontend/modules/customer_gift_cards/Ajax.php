<?php
namespace BooklyPro\Frontend\Modules\CustomerGiftCards;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib;

class Ajax extends BooklyLib\Base\Ajax
{
    /** @var BooklyLib\Entities\Customer */
    protected static $customer;

    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'customer' );
    }

    /**
     * Get customer gift cards
     */
    public static function getCustomerGiftCards()
    {
        $gift_cards = Lib\Entities\GiftCard::query( 'gc' )
            ->select( 'gc.balance, gc.code, gct.attachment_id, gct.start_date, gct.end_date, gct.amount' )
            ->leftJoin( 'GiftCardType', 'gct', 'gct.id = gc.gift_card_type_id', 'BooklyPro\Lib\Entities' )
            ->where( 'owner_id', self::$customer->getId() )
            ->where( 'customer_id', self::$customer->getId(), 'OR' )
            ->fetchArray();

        foreach ( $gift_cards as &$gift_card ) {
            $gift_card['title'] = BooklyLib\Utils\Common::getTranslatedString( 'gift_card_type_' . $gift_card['id'], $gift_card['title'] );
            $gift_card['img'] = BooklyLib\Utils\Common::getAttachmentUrl( $gift_card['attachment_id'] );
            unset( $gift_card['attachment_id'] );
        }

        wp_send_json_success( $gift_cards );
    }

    /**
     * @inheritDoc
     */
    protected static function hasAccess( $action )
    {
        if ( parent::hasAccess( $action ) ) {
            self::$customer = BooklyLib\Entities\Customer::query()->where( 'wp_user_id', get_current_user_id() )->findOne();

            return self::$customer && self::$customer->isLoaded();
        }

        return false;
    }
}