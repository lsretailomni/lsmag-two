<?php
namespace Ls\Omni\Helper;

use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;

class BasketHelper extends \Magento\Framework\App\Helper\AbstractHelper {
    public function __construct()
    {

    }

    /**
     * Fetch OneList for current contact from Omni Server
     *
     * @return \Ls\Omni\Client\Ecommerce\Entity\OneList|null
     */
    public function fetchOneList () {

        // TODO: get current ContactID
        $contactId = "MO000008";

        $request = new Operation\OneListGetByContactId();
        $entity = new Entity\OneListGetByContactId();
        /** @var Entity\ContactSearch $search */
        $entity
            ->setContactId( $contactId )
            ->setListType( Entity\Enum\ListType::BASKET )
            ->setIncludeLines( TRUE );
        $response = $request->execute($entity);

        if ( !$response ) {
            return FALSE;
        } else {

            $lists = $response->getOneListGetByContactIdResult()->getOneList();

            // if we have a list or an array, return it
            if (!is_null( $lists ) ) {
                if ($lists instanceof Entity\OneList) {
                    return $lists;
                } elseif (is_array($lists)) {
                    # return first list
                    return array_pop($lists);
                }
            }

            // if we didn't deliver one above, create a new one
            // create new list with no items and the existing offers and coupons
            $list = (new Entity\OneList())
                ->setContactId( $contactId )
                ->setDescription( 'OneList Magento' )
                ->setIsDefaultList( TRUE )
                ->setListType( Entity\Enum\ListType::BASKET )
                ->setItems( new Entity\ArrayOfOneListItem() )
                ->setOffers( $this->_offers() )
                ->setCoupons( $this->_coupons() );

            return $this->save( $list );
        }
    }
}