<?php
namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\Context;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Bundle\Model\Product\Type;
use \Magento\Checkout\Model\Cart;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Checkout\Model\Session;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ProductFactory;
use Magento\Quote\Model\Quote;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\Registry;
use Ls\Customer\Model\LSR;

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

    protected $itemHelper;
    protected $registry;

    public function __construct(
        Context $context,
        Cart $cart,
        ProductRepository $productRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        ProductFactory $productFactory,
        StockItemRepository $stockItemRepository,
        ItemHelper $itemHelper,
        Registry $registry
    )
    {
        parent::__construct($context);
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->productFactory = $productFactory;
        $this->stockItemRepository = $stockItemRepository;
        $this->itemHelper = $itemHelper;
        $this->registry = $registry;
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

        // TODO: get current ContactID when user functionality is implemented
        $contactId = $this->customerSession->getData(   LSR::SESSION_CUSTOMER_LSRID );
        if (is_null($contactId)) {
            $contactId = "MO000008";
        }


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
     * @return Entity\OneList|null
     */
    public function get() {
        /** @var Entity\OneList $list */
        $list = NULL;

        /** @var Entity\Contact $loginContact */
        if ( $loginContact = $this->registry->registry( LSR::REGISTRY_LOYALTY_LOGINRESULT ) ) {
            try {
                if ($loginContact->getOneList()->getOneList() instanceof Entity\OneList) {
                    if ($loginContact->getOneList()->getOneList()->getListType() == Entity\Enum\ListType::BASKET) {
                        return $loginContact->getOneList()->getOneList();
                    }
                } else {
                    foreach ($loginContact->getOneList()->getIterator() as $list) {
                        $isBasket = $list->getListType() == Entity\Enum\ListType::BASKET;
                        if ($isBasket && $list->getIsDefaultList()) {
                            return $list;
                        }
                    }
                }
            } catch ( Exception $e ) {
                Mage::logException( $e );
            }
        }
        // there is no onelist for the contact... let's create one
        if ( is_null( $list ) ) {
            return $this->fetchFromOmni();
        }

        return NULL;
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

                    /** @var \Magento\Catalog\Model\Product $product */
                    $product = $this->itemHelper->product($item, $listItem->getVariant());

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
                                'qty' => $listItem->getQuantity(),
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
                            $cart->addProduct( $product, array( 'qty' => intval( $listItem->getQuantity() ) ) );
                        } catch ( Exception $e ) {
                            Mage::getSingleton( 'checkout/session' )
                                ->addError( $product->getData( 'name' ) .
                                    ' is not available' );
                        }
                    }

                    //TODO: i18n
                    $notice = <<<MESSAGE
Product "{$list_item->getItem()->getDescription()}" is not available and was not added to the cart
MESSAGE;
                    Mage::getSingleton( 'catalog/session' )
                        ->addNotice( $notice );
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
    public function compare(Entity\OneList $oneList, Quote $quote) {
        /** @var Entity\OneListItem[] $onlyInOneList */
        /** @var Entity\OneListItem[] $onlyInQuote */
        $onlyInOneList = array();
        $onlyInQuote = array();

        /** @var \Magento\Quote\Model\Quote\Item[] $quoteItems */
        $cache = array();
        $quoteItems = $quote->getAllVisibleItems();

        /** @var Entity\OneListItem[] $oneListItems */
        $oneListItems = !is_null( $oneList->getItems()->getOneListItem() )
            ? $oneList->getItems()->getOneListItem()
            : array();

        foreach ( $oneListItems as $oneListItem ) {

            $found = FALSE;

            foreach ( $quoteItems as $quoteItem ) {
                // TODO: check Product Type
                $isConfigurable = $quoteItem->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_CONFIGURABLE;
                if ( isset( $cache[ $quoteItem->getId() ] ) || $isConfigurable ) {
                    continue;
                }

                $productLsrId = $this->productFactory->create()
                    ->load( $quoteItem->getProduct()->getId() )
                    ->getData( 'lsr_id' );
                $quote_has_item = $productLsrId == $oneListItem->getItem()->getId();
                $qi_qty = $quoteItem->getData( 'qty' );
                $item_qty = intval( $oneListItem->getQuantity() );
                $match = $quote_has_item && ( $qi_qty == $item_qty );

                if ( $match ) {
                    $cache[ $quoteItem->getId() ] = $found = TRUE;
                    break;
                }
            }

            // if found is still false, the item is not presend in the quote
            if ( !$found ) {
                $onlyInOneList[] = $oneListItem;
            }
        }

        foreach ( $quoteItems as $quoteItem ) {
            $isConfigurable = $quoteItem->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_CONFIGURABLE;

            // if the item is in the cache, it is present in the oneList and the quote
            if ( isset( $cache[ $quoteItem->getId() ] ) || $isConfigurable ) {
                continue;
            }

            $onlyInQuote[] = $quoteItem;
        }

        return array($onlyInQuote, $onlyInOneList);
    }

    /**
     * @param Quote $quote
     * @param Entity\OneList $basket
     * @return Entity\OneList
     * @deprecated Use setOneListQuote instead
     * @see BasketHelper::setOneListQuote() Use this function instead, has better naming
     */
    public function basket(Quote $quote, Entity\OneList $basket) {
        return $this->setOneListQuote($quote, $basket);
    }

    /**
     * Set the Quote inside the OneList
     * @param Quote $quote
     * @param Entity\OneList $oneList
     * @return Entity\OneList
     */
    public function setOneListQuote(Quote $quote, Entity\OneList $oneList) {
        // TODO: test when the Quote actually works

        /** @var \Magento\Quote\Model\Quote\Item[] $quoteItems */
        $quoteItems = $quote->getAllVisibleItems();

        $items = new Entity\ArrayOfOneListItem();
        $itemsArray = array();
        foreach ( $quoteItems as $quoteItem ) {

            // TODO: check if lsr_id is not loaded to quote_item product
            $sku = $quoteItem->getSku();
            $parts = explode( '.', $sku );

            // delete first element of $parts, which is the magento id
            array_shift( $parts );
            // second element is lsr_id
            $lsr_id = array_shift( $parts );
            // third element, if it exists, is variant id
            $variant_id = count( $parts ) ? array_shift( $parts ) : NULL;

            $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku',$sku, 'eq')->create();
            $productList = $this->productRepository->getList($searchCriteria)->getItems();
            $product = array_pop($productList);

            // TODO: remove if replication works
            if ($sku == "Test Product 1") {
                $lsr_id = "40045";
                $product->setData('lsr_barcode', '5699100005869');
            }

            $item = $this->itemHelper->get( $lsr_id );
            $uom = $this->itemHelper->uom($item);

            $variant = is_null( $variant_id )
                ? NULL
                : ( new Entity\Variant() )
                    ->setItemId( $item->getId() )
                    ->setId( $variant_id );

            $list_item = ( new Entity\OneListItem() )
                ->setQuantity( $quoteItem->getData( 'qty' ) )
                ->setItem( $this->itemHelper->lite( $item ) )
                ->setBarcodeId( $product->getData( 'lsr_barcode' ) ? $product->getData( 'lsr_barcode' ) : '' )
                ->setVariant( $variant )
                ->setId( '' )
                ->setUom( $uom );

            $itemsArray[] = $list_item ;
        }
        $items->setOneListItem($itemsArray);

        $oneList->setItems( $items )
            ->setOffers( $this->_offers() )
            ->setCoupons( $this->_coupons() );

        if ( empty( $oneList->getCardId() ) ) {
            $oneList->setCardId( NULL );
        }
        if ( empty( $oneList->getCustomerId() ) ) {
            $oneList->setCustomerId( NULL );
        }

        return $oneList;
    }


    public function delete(Entity\OneList $oneList) {
        $entity = new Entity\OneListDeleteById();
        $entity
            ->setOneListId($oneList->getId())
            ->setListType(Entity\Enum\ListType::BASKET);
        $request = new Operation\OneListDeleteById();
        $response = $request->execute($entity);

        return $response ? $response->getOneListDeleteByIdResult() : FALSE;
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

        $contactId = $this->customerSession->getData(   LSR::SESSION_CUSTOMER_LSRID );
        if (is_null($contactId)) {
            $contactId = "MO000008";
        }
        #$storeId = LSR::getStoreConfig( LSR::SC_OMNICLIENT_STORE );
        $cardId = $this->customerSession->getData( LSR::SESSION_CUSTOMER_CARDID );
        $storeId = "S0013";
        $cardId = 10021;

        /** @var Entity\BasketCalcResponse $response */
        $response = FALSE;

        if ( !is_null( $oneListItems->getOneListItem() ) ) {

            $array = array();
            $n = 1;

            /** @var Entity\OneListItem $listItem */
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

            // TODO: support coupons
            /*
            $coupon = $quote->getData( LSR::ATTRIBUTE_COUPON_CODE);
            if ( !is_null( $coupon ) ) {
                $line = ( new Entity\BasketCalcLineRequest() )
                    ->setLineNumber( $n )
                    ->setCouponCode( $coupon )
                    ->setQuantity( 1 )
                    ->setUomId( NULL );
                $array[] = $line;
            }
            */

            $lineRequest = new Entity\ArrayOfBasketCalcLineRequest();
            $lineRequest->setBasketCalcLineRequest($array);

            $basketCalcRequest = ( new Entity\BasketCalcRequest() )
                ->setContactId( $contactId )
                ->setCardId( $cardId )
                ->setItemType( Entity\Enum\BasketCalcItemType::ITEM_NO )
                ->setId( $oneList->getId() )
                ->setBasketCalcLineRequests( $lineRequest )
                ->setStoreId( $storeId );

            // TODO: support store type
            /*
            $store = LSR::getStore();
            if ( LSR::isW1( $store ) ) {
                $basketCalcRequest->setCalcType( Entity\Enum\BasketCalcType::TYPE_FINAL );
            } else {
                if ( LSR::isNA( $store ) ) {
                    $basketCalcRequest->setCalcType( Entity\Enum\BasketCalcType::COLLECT );
                }
            }
            */

            $entity = new Entity\BasketCalc();
            $entity->setBasketRequest($basketCalcRequest);
            $request = new Operation\BasketCalc();
            $response = $request->execute($entity);

            // TODO: add logging
        }
        // TODO: add access to actual result when possible
        return !is_null($response) ? $response->getBasketCalcResult() : NULL;
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