<?php
namespace BooklyPro\Backend\Modules\Customers\ProxyProviders;

use Bookly\Backend\Modules\Customers\Proxy;
use BooklyPro\Lib;

class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function mergeCustomers( $target_id, array $ids )
    {
        Lib\Entities\GiftCard::query()
            ->update()
            ->set( 'customer_id', $target_id )
            ->whereIn( 'customer_id', $ids )
            ->execute();
        Lib\Entities\GiftCard::query()
            ->update()
            ->set( 'owner_id', $target_id )
            ->whereIn( 'owner_id', $ids )
            ->execute();
    }
}