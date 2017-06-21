<?php
namespace Ls\Omni\Helper;

use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Bundle\Model\Product\Type;
use \Magento\Checkout\Model\Cart;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Checkout\Model\Session;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ProductFactory;
use Magento\Quote\Model\Quote;

class BasketHelper extends \Magento\Framework\App\Helper\AbstractHelper {

    /** @var Cart $cart */
    protected $cart;
    /** @var \Magento\Catalog\Model\ProductRepository $productRepository */
    protected $productRepository;
    /** @var Session $checkoutSession */
    protected $checkoutSession;
    /** @var \Magento\Customer\Model\Session $customerSession */
    protected $customerSession;
    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    protected $searchCriteriaBuilder;
    /** @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable */
    protected $catalogProductTypeConfigurable;
    /** @var ProductFactory $productFactory */
    protected $productFactory;
    /** @var StockItemRepository $stockItemRepository */
    protected $stockItemRepository;

    public function __construct(
        Cart $cart,
        ProductRepository $productRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        ProductFactory $productFactory,
        StockItemRepository $stockItemRepository
    )
    {
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->productFactory = $productFactory;
        $this->stockItemRepository = $stockItemRepository;
    }

    /**
     * @deprecated Function renamed to fetchFromOmni
     * @return \Ls\Omni\Client\Ecommerce\Entity\OneList|null|bool
     */
    public function fetch() {
        return $this->fetchFromOmni();
    }

    /**
     * Fetch OneList for current contact from Omni Server
     * @return \Ls\Omni\Client\Ecommerce\Entity\OneList|null|bool
     */
    public function fetchFromOmni () {

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
            // save back to Omni so we have an empty list there
            return $this->saveToOmni( $list );
        }
    }

    /**
     * Get OneList from currently logged in User from Magento if there is one
     * @return Entity\OneList
     */
    public function get() {
        throw new Exception("Not yet implemented.");
        // TODO: load from magento
        // in Magento 1, we stored the user in the registry
        // if there is no OneList in this user, call fetchFromOmni()
        if (is_null($list)) {
            return $this->fetchFromOmni();
        }
    }

    /**
     * @param Entity\OneList $list
     * @deprecated renamed to saveToOmni
     * @return Entity\OneList|false
     */
    public function save(Entity\OneList $list) {
        $this->saveToOmni($list);
    }

    /**
     * Save OneList to Omni Server
     * @param Entity\OneList $list
     *
     * @return Entity\OneList|false
     */
    public function saveToOmni ( Entity\OneList $list ) {
        $operation = new Operation\OneListSave();
        // TODO: tokenized
        $request = (new Entity\OneListSave())->setOneList( $list );
        $response = $operation->execute($request);

        return $response ? $response->getOneListSaveResult() : FALSE;
    }

    /**
     * Get offers
     * @return Entity\ArrayOfOneListOffer
     */
    private function _offers () {
        // TODO: actually load offers
        $offers = new Entity\ArrayOfOneListOffer();
        return $offers;
    }

    /**
     * @return Entity\ArrayOfOneListCoupon
     */
    private function _coupons () {
        // TODO: actually load coupons
        $coupons = new Entity\ArrayOfOneListCoupon();
        return $coupons;
    }

    /**
     * @param Entity\OneList $list
     * @deprecated renamed to storeAsCart
     * @return Cart
     */
    public function cart( Entity\OneList $list) {
        return $this->storeAsCart($list);
    }

    /**
     * @param Entity\OneList $list
     * Store the given OneList as the cart, while doing availability checks
     * @return Cart
     */
    public function storeAsCart(Entity\OneList $oneList) {
        $basket = $oneList;

        // TODO: remove alias
        $cart = $this->cart;

        // if the oneList has items
        if ( $items = $oneList->getItems() ) {
            // TODO: remove this alias
            $session = $this->checkoutSession;
            // get quote from checkout session
            $quote = $this->checkoutSession->getQuote();

            // set quote of cart, but remove all items
            $this->cart->setQuote( $quote )->truncate();

            // if we have a multiple items in the itemsList
            if ( !is_null( $items->getOneListItem() ) ) {

                // cycle through each item
                /** @var Entity\OneListItem $listItem */
                foreach ( $items->getOneListItem() as $listItem ) {

                    // TODO: no variants
                    $item = $listItem->getItem();
                    $lsr_id = $item->getId();

                    // build lsr_id when there is a variant
                    if ( !is_null( $listItem->getVariant() ) ) {
                        $lsr_id = join( '.', array( $item->getId(), $listItem->getVariant()->getId() ) );
                    }

                    // find right product by lsr_id
                    $searchCriteria = $this->searchCriteriaBuilder->addFilter('lsr_id',$lsr_id, 'like')->create();
                    $productList = $this->productRepository->getList($searchCriteria);
                    if ($productList->getTotalCount() > 1) {
                        // TODO: what do we do?!?
                    } elseif ($productList->getTotalCount() == 1) {
                        // if only one product is found

                        /** @var \Magento\Catalog\Model\Product $product */
                        $product = $productList[0];

                        // if there is a . in the lsr_id, we have a variant
                        if ( strpos( $lsr_id, '.' ) != FALSE ) {
                            // find parent product
                            $parent_ids = $this->catalogProductTypeConfigurable->getParentIdsByChild($product->getId());
                            // check if something is returned
                            if ( !empty( array_filter( $parent_ids ) ) ) {
                                $pid = $parent_ids[0];

                                // load parent product
                                $configurable = $this->productFactory->create()->load($pid);

                                // now we need to find out which actual variant is loaded
                                // in Mag1, we did this by loading all attributes with their data
                                // and sending this together with the parent product to $cart->addProduct
                                // here is a similar approach for Mag2:
                                // https://webkul.com/blog/get-configurable-associated-products-id-magento2/

                                // CHANGED CODE ONLY UNTIL HERE
                                $attribute_options = $configurable->getTypeInstance( TRUE )
                                    ->getConfigurableAttributesAsArray( $configurable );
                                $options = array();
                                // load optionsarray
                                foreach ( $attribute_options as $attribute ) {
                                    $values = array_column( $attribute[ 'values' ], 'value_index' );
                                    $current = $product->getData( $attribute[ 'attribute_code' ] );
                                    if ( in_array( $current, $values ) ) {
                                        $options[ $attribute[ 'attribute_id' ] ] = $current;
                                    }
                                }

                                // Get cart instance
                                $cart = Mage::getSingleton( 'checkout/cart' );
                                $cart->init();
                                // Add a product with custom options
                                $params = array(
                                    'product' => $configurable->getId(),
                                    'qty' => $list_item->getQuantity(),
                                    'super_attribute' => $options,
                                );
                                $request = new Varien_Object();
                                $request->setData( $params );
                                try {
                                    $cart->addProduct( $configurable, $request );
                                } catch ( Exception $e ) {
                                    Mage::getSingleton( 'checkout/session' )
                                        ->addError( $configurable->getData( 'name' ) .
                                            ' is not available' );
                                }
                            }
                        } else {
                            // no . in lsr_id => product without variants
                            try {
                                $cart->addProduct( $product, array( 'qty' => intval( $list_item->getQuantity() ) ) );
                            } catch ( Exception $e ) {
                                Mage::getSingleton( 'checkout/session' )
                                    ->addError( $product->getData( 'name' ) .
                                        ' is not available' );
                            }
                        }
                    } else {
                        // product loading failed

                        //TODO: i18n
                        $notice = <<<MESSAGE
Product "{$list_item->getItem()->getDescription()}" is not available and was not added to the cart
MESSAGE;
                        Mage::getSingleton( 'catalog/session' )
                            ->addNotice( $notice );
                    }
                }
            }
        }

        return $cart;
    }

    /**
     * Compared a OneList with a quote and returns an array which contains
     * the items present only in the quote and only in the OneList (basket)
     * @param Entity\OneList $list
     * @param Quote $quote
     * @return array
     */
    public function compare(Entity\OneList $list, Quote $quote) {
        throw new Exception("Not yet implemented.");
        $onlyInOneList = array();
        $onlyInQuote = array();
        return array($onlyInQuote, $onlyInOneList);
    }

    /**
     * @param Quote $quote
     * @param Entity\OneList $basket
     * @deprecated
     */
    public function basket(Quote $quote, Entity\OneList $basket) {
        return $this->setOneListQuote($quote,$basket);
    }

    /**
     * Set the Quote inside the OneList
     * @param Quote $quote
     * @param Entity\OneList $oneList
     * @return Entity\OneList
     */
    public function setOneListQuote(Quote $quote, Entity\OneList $oneList) {
        // TODO: convert the items inside the $quote to OneListItem s and store them to the OneList
        throw new Exception("Not yet implemented.");
        return $oneList;
    }


    public function delete(Entity\OneList $list) {
        throw new Exception("Not yet implemented.");
    }

    /**
     * Calculate current price and check inventory
     * @param Entity\OneList $oneList
     */
    public function update(Entity\OneList $oneList) {
        $cart_helper = Mage::helper( 'checkout/cart' );

        $calculation = $this->calculate( $oneList );
        $this->checkoutSession->setData( LSR::SESSION_CHECKOUT_BASKET, $oneList );
        $this->checkoutSession->setData( LSR::SESSION_CHECKOUT_BASKETCALCULATION, $calculation );

        $check_inventory = LSR::getStoreConfig( LSR::SC_CART_CHECK_INVENTORY );
        $update_inventory = LSR::getStoreConfig( LSR::SC_CART_UPDATE_INVENTORY );

        if ( $check_inventory ) {
            /** @var Entity\ArrayOfOrderLineAvailability $availability */
            $availability = $this->availability( $oneList );
            /** @var OrderLineAvailability[] $availabilityLines */
            if ( $availability && $availabilityLines = $availability->getOrderLineAvailability() ) {

                $quote = $this->checkoutSession->getQuote();
                /** @var \Magento\Quote\Model\Quote\Item[] $quoteItems */
                $quoteItems = $quote->getAllVisibleItems();

                foreach ( $availabilityLines as $availabilityLine ) {

                    $productLsrId = $availabilityLine->getItemId();
                    if ( !is_empty_date( $availabilityLine->getVariantId() ) ) {
                        # build LSR Id
                        $productLsrId = join(
                            '.',
                            array($availabilityLine->getItemId(),$availabilityLine->getVariantId())
                        );
                    }
                    $stock = intval( $availabilityLine->getQuantity() );

                    $searchCriteria = $this->searchCriteriaBuilder->addFilter('lsr_id',$productLsrId, 'like')->create();
                    $productList = $this->productRepository->getList($searchCriteria);

                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $productList[0];

                    if( $product->getId() ){
                        $stockItem = $this->stockItemRepository->get($product->getId());

                        if ( !$stockItem->getId() ) {
                            $stockItem->setData( 'product_id', $product->getId() );
                            $stockItem->setData( 'stock_id', 1 );
                        }

                        $isInStock = $stock > 0 ? 1 : 0;
                        $stockItem
                            ->setData( 'is_in_stock', $isInStock )
                            ->setData( 'manage_stock', 0 )
                            ->setData( 'qty', $stock );

                        $stockItem->save();

                        if ( !$isInStock && $update_inventory ) {

                            // avoid endless loop
                            // $cart->save() calls the event checkout_cart_save_after which
                            // calls LSR_Omni_Model_Observer_Cart::update_basket() which calls this function
                            //$watchNextSave = Mage::registry( LSR::REGISTRY_LOYALTY_WATCHNEXTSAVE );
                            //if ($watchNextSave) {
                                //Mage::unregister( LSR::REGISTRY_LOYALTY_WATCHNEXTSAVE );
                            //}

                            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
                            foreach ( $quoteItems as $quoteItem ) {
                                $isConfigurable = $quoteItem->getData( 'product_type' ) ==
                                    Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
                                if ( $isConfigurable ) {
                                    /** @var Quote\Item $childQuoteItem */ // not sure
                                    $childQuoteItem = array_pop( $quoteItem->getChildren() );
                                    if ( $product->getId() == $childQuoteItem->getProduct()->getId() ) {
                                        $this->cart->removeItem( $quoteItem->getData( 'item_id' ) );
                                        // check if this is necessary
                                        $this->cart->save();

                                    }
                                } else {
                                    if ( $product->getId() == $quoteItem->getProduct()->getId() ) {
                                        $this->cart->removeItem( $quoteItem->getData( 'item_id' ) );
                                        // check if this is necessary
                                        $this->cart->save();
                                    }
                                }
                            }
                            // restore previous state if someone didn't already
                            //if ($watchNextSave && !Mage::registry( LSR::REGISTRY_LOYALTY_WATCHNEXTSAVE )) {
                                //Mage::register( LSR::REGISTRY_LOYALTY_WATCHNEXTSAVE, $watchNextSave );
                            //}
                        }
                    }
                }
            }
        }
        // store the updated basket to Omni
        // needs a OneList
        $this->saveToOmni($oneList);
    }

    /**
     * Load Shipment Fee Product
     */
    public function getShipmentFeeProduct() {

    }

    /**
     * Call BasketCalc on the OneList
     * @param Entity\OneList $oneList
     * @return false|Entity\BasketCalcResponse
     */
    public function calculate(Entity\OneList $oneList) {
        $oneListItems = $oneList->getItems();
        // TODO: use real data
        #$shipmentFee = $this->getShipmentFeeProdut();
        #$shipmentFeeId = $shipmentFee->getData('lsr_id');
        $shipmentFeeId = 66010;

        $response = FALSE;

        if ( !is_null( $oneListItems->getOneListItem() ) ) {

            $array = array();
            $n = 1;

            /** @var LSR_Omni_Model_Omni_Domain_OneListItem $listItem */
            foreach ( $oneListItems->getOneListItem() as $listItem ) {

                $item = $listItem->getItem();
                $uom = $listItem->getUom();

                $line = ( new Entity\BasketCalcLineRequest() )
                    ->setLineNumber( $n++ )
                    ->setItemId( $item->getId() )
//                 ->setExternalId( $item->getId() )
                    ->setQuantity( $listItem->getQuantity() )
                    ->setUomId( !is_null( $uom ) ? $uom->getId() : NULL );
                if ( !is_null( $listItem->getVariant() ) ) {
                    $line->setVariantId( $listItem->getVariant()->getId() );
                }

                $array[] = $line;
                unset( $line );
            }


            $quote = $this->checkoutSession->getQuote();
            $shippingAddress = $quote->getShippingAddress();
            $shippingAmount = $shippingAddress->getShippingAmount();

            if ( !is_null( $shippingAmount ) && $shippingAmount > 0 ) {
                $line = ( new Entity\BasketCalcLineRequest() )
                    ->setLineNumber( $n++ )
                    ->setItemId( $shipmentFeeId )
                    ->setQuantity( $shippingAmount )
                    ->setUomId( NULL );
                $array[] = $line;
                unset( $line );
            }

            $coupon = $quote->getData( LSR::ATTRIBUTE_COUPON_CODE);
            if ( !is_null( $coupon ) ) {
                $line = ( new Entity\BasketCalcLineRequest() )
                    ->setLineNumber( $n )
                    ->setCouponCode( $coupon )
                    ->setQuantity( 1 )
                    ->setUomId( NULL );
                $array[] = $line;
            }

            $lines = new Entity\ArrayOfBasketCalcLineRequest();
            $lines->setBasketCalcLineRequest($array);

            $basketCalcRequest = ( new Entity\BasketCalcRequest() )
                ->setContactId( $this->customerSession->getData( LSR::SESSION_CUSTOMER_LSRID ) )
                ->setCardId( $this->customerSession->getData( LSR::SESSION_CUSTOMER_CARDID ) )
                ->setItemType( Entity\Enum\BasketCalcItemType::ITEM_NO )
                ->setId( $oneList->getId() )
                ->setBasketCalcLineRequests( $lines )
                ->setStoreId( LSR::getStoreConfig( LSR::SC_OMNICLIENT_STORE ) );

            $store = LSR::getStore();
            if ( LSR::isW1( $store ) ) {
                $basketCalcRequest->setCalcType( Entity\Enum\BasketCalcType::TYPE_FINAL );
            } else {
                if ( LSR::isNA( $store ) ) {
                    $basketCalcRequest->setCalcType( Entity\Enum\BasketCalcType::COLLECT );
                }
            }

            $request = new Operation\BasketCalc();
            $response = $request->execute($basketCalcRequest);

            LSR::getLogger()
                ->dump( $request->__request()->getData( LSR_Omni_Model_Omni_Service_BaseRequest::OPERATION_REQUEST ) );
            LSR::getLogger()
                ->dump( $request->__request()->getData( LSR_Omni_Model_Omni_Service_BaseRequest::OPERATION_RESPONSE ) );
        }

        return $response ? $response->getBasketCalcResult() : $response;
    }

    /**
     * Check availability of the items
     * @param Entity\OneList $oneList
     * @return Entity\ArrayOfOrderLineAvailability
     */
    public function availability(Entity\OneList $oneList) {
        $oneListItems = $oneList->getItems();
        $response = FALSE;

        if ( !is_null( $oneListItems->getOneListItem() ) ) {

            $array = array();

            $count = 1;
            /** @var Entity\OneListItem $listItem */
            foreach ( $oneListItems->getOneListItem() as $listItem ) {
                $variant = $listItem->getVariant();
                $uom = !is_null( $listItem->getUom() ) ? $listItem->getUom()->getId() : NULL;
                $line = ( new Entity\OrderLineAvailability() )
                    ->setItemId( $listItem->getItem()->getId() )
                    ->setLineType( Entity\Enum\LineType::ITEM)
                    ->setUomId( $uom )
                    ->setLineNumber( $count++ )
                    ->setQuantity( $listItem->getQuantity() )
                    ->setVariantId( is_null( $variant ) ? NULL : $variant->getId() );
                $array[] = $line;
                unset( $line );
            }

            $lines = new Entity\ArrayOfOrderLineAvailability();
            $lines->setOrderLineAvailability($array);

            // TODO: get actual store and CardId
            #$storeId = LSR::getStoreConfig( LSR::SC_OMNICLIENT_STORE );
            #$cardId = $this->customerSession->getData( LSR::SESSION_CUSTOMER_CARDID );
            $storeId = "S0013";
            $cardId = 10021;

            $request = ( new Entity\OrderAvailabilityRequest() )
                ->setStoreId( $storeId )
                ->setCardId( $cardId )
                ->setSourceType( Entity\Enum\SourceType::STANDARD )
                ->setItemNumberType( Entity\Enum\ItemNumberType::ITEM_NO )
                ->setOrderLineAvailabilityRequests( $lines );
            $entity = new Entity\OrderAvailabilityCheck();
            $entity->setRequest($request);
            $operation = new Operation\OrderAvailabilityCheck();
            $response = $operation->execute($entity);
        }

        return $response ? $response->getOrderAvailabilityCheckResult() : $response;
    }
}