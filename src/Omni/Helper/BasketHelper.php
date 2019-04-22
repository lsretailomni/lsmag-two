<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\Context;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ProductFactory;
use Magento\Quote\Model\Quote;
use Magento\Framework\Registry;
use \Ls\Core\Model\LSR;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class BasketHelper
 * @package Ls\Omni\Helper
 */
class BasketHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var Cart $cart */
    public $cart;

    /** @var \Magento\Catalog\Model\ProductRepository $productRepository */
    public $productRepository;

    /**
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    public $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\
     * Type\Configurable $catalogProductTypeConfigurable
     */
    public $catalogProductTypeConfigurable;

    /** @var ProductFactory $productFactory */
    public $productFactory;

    /** @var ItemHelper $itemHelper */
    public $itemHelper;

    /** @var Registry $registry */
    public $registry;

    /** @var null|string */
    public $store_id = null;

    /** @var  LSR $lsr */
    public $lsr;

    /** @var array */
    public $basketDataResponse;

    /**
     * @var SessionManagerInterface
     */
    public $session;

    /**
     * @var $quoteRepository
     */
    public $quoteRepository;

    /**
     * @var $couponCode
     */
    public $couponCode;

    /**
     * BasketHelper constructor.
     * @param Context $context
     * @param Cart $cart
     * @param ProductRepository $productRepository
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
     * @param ProductFactory $productFactory
     * @param ItemHelper $itemHelper
     * @param Registry $registry
     * @param LSR $Lsr
     * @param SessionManagerInterface $session
     */
    public function __construct(
        Context $context,
        Cart $cart,
        ProductRepository $productRepository,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        ProductFactory $productFactory,
        ItemHelper $itemHelper,
        Registry $registry,
        LSR $Lsr,
        SessionManagerInterface $session,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->productFactory = $productFactory;
        $this->itemHelper = $itemHelper;
        $this->registry = $registry;
        $this->lsr = $Lsr;
        $this->session = $session;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Compared a OneList with a quote and returns an array which contains
     * the items present only in the quote and only in the OneList (basket)
     * @param Entity\OneList $list
     * @param Quote $quote
     * @return array
     */
    public function compare(Entity\OneList $oneList, Quote $quote)
    {
        /** @var Entity\OneListItem[] $onlyInOneList */
        /** @var Entity\OneListItem[] $onlyInQuote */
        $onlyInOneList = [];
        $onlyInQuote = [];

        /** @var \Magento\Quote\Model\Quote\Item[] $quoteItems */
        $cache = [];
        $quoteItems = $quote->getAllVisibleItems();

        /** @var Entity\OneListItem[] $oneListItems */
        $oneListItems = !($oneList->getItems()->getOneListItem() == null)
            ? $oneList->getItems()->getOneListItem()
            : [];

        foreach ($oneListItems as $oneListItem) {
            $found = false;

            foreach ($quoteItems as $quoteItem) {
                $isConfigurable = $quoteItem->getProductType()
                    == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
                if (isset($cache[$quoteItem->getId()]) || $isConfigurable) {
                    continue;
                }
                // @codingStandardsIgnoreStart
                $productLsrId = $this->productFactory->create()
                    ->load($quoteItem->getProduct()->getId())
                    ->getData('lsr_id');
                // @codingStandardsIgnoreEnd
                $quote_has_item = $productLsrId == $oneListItem->getItem()->getId();
                $qi_qty = $quoteItem->getData('qty');
                $item_qty = (int)($oneListItem->getQuantity());
                $match = $quote_has_item && ($qi_qty == $item_qty);

                if ($match) {
                    $cache[$quoteItem->getId()] = $found = true;
                    break;
                }
            }

            // if found is still false, the item is not presend in the quote
            if (!$found) {
                $onlyInOneList[] = $oneListItem;
            }
        }

        foreach ($quoteItems as $quoteItem) {
            $isConfigurable = $quoteItem->getProductType()
                == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;

            // if the item is in the cache, it is present in the oneList and the quote
            if (isset($cache[$quoteItem->getId()]) || $isConfigurable) {
                continue;
            }
            $onlyInQuote[] = $quoteItem;
        }

        return [$onlyInQuote, $onlyInOneList];
    }

    /**
     * @param Quote $quote
     * @param Entity\OneList $oneList
     * @return Entity\OneList
     */
    public function setOneListQuote(Quote $quote, Entity\OneList $oneList)
    {
        $shipmentFeeId = LSR::LSR_SHIPMENT_ITEM_ID;

        /** @var \Magento\Quote\Model\Quote\Item[] $quoteItems */
        $quoteItems = $quote->getAllVisibleItems();
        if (count($quoteItems) == 0) {
            $this->unsetCouponCode("");
        }

        /** @var Entity\ArrayOfOneListItem $items */
        // @codingStandardsIgnoreLine
        $items = new Entity\ArrayOfOneListItem();

        $itemsArray = [];

        foreach ($quoteItems as $quoteItem) {
            // initialize the default null value
            $variant = $barcode = null;

            $sku = $quoteItem->getSku();

            $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $sku, 'eq')->create();

            $productList = $this->productRepository->getList($searchCriteria)->getItems();

            /** @var \Magento\Catalog\Model\Product\Interceptor $product */
            $product = array_pop($productList);

            $barcode = $product->getData('barcode');

            $parts = explode('-', $sku);
            // first element is lsr_id
            $lsr_id = array_shift($parts);
            // second element, if it exists, is variant id
            // @codingStandardsIgnoreLine
            $variant_id = count($parts) ? array_shift($parts) : null;

            /** @var \Ls\Omni\Client\Ecommerce\Entity\LoyItem $item */
            $item = $this->itemHelper->get($lsr_id);

            if (!($variant_id == null)) {
                /** @var Entity\VariantRegistration|null $variant */
                $variant = $this->itemHelper->getItemVariant($item, $variant_id);
            }
            /** @var Entity\UnitOfMeasure|null $uom */
            $uom = $this->itemHelper->uom($item);
            // @codingStandardsIgnoreLine
            $list_item = (new Entity\OneListItem())
                ->setQuantity($quoteItem->getData('qty'))
                ->setItem($item)
                ->setId('')
                ->setBarcodeId($barcode)
                ->setVariantReg($variant)
                ->setUnitOfMeasure($uom);

            $itemsArray[] = $list_item;
        }

        //Custom Shipment Line
        /** @var Entity\LoyItem $shipmentItem */
        // @codingStandardsIgnoreLine
        $shipmentItem = (new Entity\LoyItem())
            ->setId($shipmentFeeId);
        /** @var Entity\OneListItem $shipmentLine */
        // @codingStandardsIgnoreLine
        $shipmentLine = (new Entity\OneListItem())
            ->setId('')
            ->setItem($shipmentItem)
            ->setQuantity(1);
        array_push($itemsArray, $shipmentLine);

        $items->setOneListItem($itemsArray);

        $oneList->setItems($items)
            ->setPublishedOffers($this->_offers());

        return $oneList;
    }

    /**
     * @return Entity\ArrayOfOneListPublishedOffer
     */
    private function _offers()
    {
        // @codingStandardsIgnoreLine
        $offers = new Entity\ArrayOfOneListPublishedOffer();

        return $offers;
    }

    /**
     * @param Entity\OneList $oneList
     * @return bool
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function delete(Entity\OneList $oneList)
    {

        /** @var Entity\OneListDeleteById $entity */
        // @codingStandardsIgnoreLine
        $entity = new Entity\OneListDeleteById();

        $entity->setOneListId($oneList->getId());
        // @codingStandardsIgnoreLine
        $request = new Operation\OneListDeleteById();

        /** @var  Entity\OneListDeleteByIdResponse $response */
        $response = $request->execute($entity);

        return $response ? $response->getOneListDeleteByIdResult() : false;
    }

    /**
     * @param Entity\OneList $oneList
     * @return Entity\OneListCalculateResponse|null
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    // @codingStandardsIgnoreLine
    public function update(Entity\OneList $oneList)
    {
        $this->saveToOmni($oneList);
        $basketData = $this->calculate($oneList);
        if (is_object($basketData)) {
            $this->setOneListCalculation($basketData);
            if (isset($basketData)) {
                $this->unSetBasketSessionValue();
                $this->setBasketSessionValue($basketData);
            }
        } else {
            return $basketData;
        }

        return $basketData;
    }

    /**
     * @param Entity\OneList $oneList
     * @return bool|Entity\ArrayOfOrderLineAvailability|Entity\OrderAvailabilityCheckResponse|\Ls\Omni\Client\ResponseInterface
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function availability(Entity\OneList $oneList)
    {
        $oneListItems = $oneList->getItems();
        $response = false;

        if (!($oneListItems->getOneListItem() == null)) {
            $array = [];

            $count = 1;
            /** @var Entity\OneListItem $listItem */

            foreach ($oneListItems->getOneListItem() as $listItem) {
                $variant = $listItem->getVariant();
                $uom = !($listItem->getUom() == null) ? $listItem->getUom()[0]->getId() : null;
                // @codingStandardsIgnoreLine
                $line = (new Entity\OrderLineAvailability())
                    ->setItemId($listItem->getItem()->getId())
                    ->setLineType(Entity\Enum\LineType::ITEM)
                    ->setUomId($uom)
                    ->setLineNumber($count++)
                    ->setQuantity($listItem->getQuantity())
                    ->setVariantId(($variant == null) ? null : $variant->getId());
                $array[] = $line;
                unset($line);
            }
            // @codingStandardsIgnoreStart
            $lines = new Entity\ArrayOfOrderLineAvailability();
            $lines->setOrderLineAvailability($array);

            $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);

            $request = (new Entity\OrderAvailabilityRequest())
                ->setStoreId($this->getDefaultWebStore())
                ->setCardId($cardId)
                ->setSourceType(Entity\Enum\SourceType::STANDARD)
                ->setItemNumberType(Entity\Enum\ItemNumberType::ITEM_NO)
                ->setOrderLineAvailabilityRequests($lines);
            $entity = new Entity\OrderAvailabilityCheck();
            $entity->setRequest($request);
            $operation = new Operation\OrderAvailabilityCheck();
            // @codingStandardsIgnoreEnd
            $response = $operation->execute($entity);
        }

        return $response ? $response->getOrderAvailabilityCheckResult() : $response;
    }

    /**
     * @return null|string
     */
    public function getDefaultWebStore()
    {
        if ($this->store_id == null) {
            $this->store_id = $this->lsr->getDefaultWebStore();
        }

        return $this->store_id;
    }

    /**
     * @param Entity\OneList $list
     * @return bool|Entity\OneList
     */
    public function saveToOmni(Entity\OneList $list)
    {

        /** @var Operation\OneListSave $operation */
        // @codingStandardsIgnoreLine
        $operation = new Operation\OneListSave();

        $list->setStoreId($this->getDefaultWebStore());

        /** @var Entity\OneListSave $request */
        // @codingStandardsIgnoreLine
        $request = (new Entity\OneListSave())
            ->setOneList($list)
            ->setCalculate(true);

        /** @var Entity\OneListSaveResponse $response */
        $response = $operation->execute($request);
        if ($response) {
            $this->customerSession->setData(LSR::SESSION_CART_ONELIST, $response->getOneListSaveResult());
            return $response->getOneListSaveResult();
        }

        return false;
    }

    /**
     * @param Entity\OneList $oneList
     * @return Entity\OneListCalculateResponse|null
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    // @codingStandardsIgnoreLine
    public function calculate(Entity\OneList $oneList)
    {
        // @codingStandardsIgnoreLine
        $storeId = $this->getDefaultWebStore();
        $contactId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID);
        $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);

        /** @var Entity\ArrayOfOneListItem $oneListItems */
        $oneListItems = $oneList->getItems();

        /** @var Entity\OneListCalculateResponse $response */
        $response = false;

        if (!($oneListItems->getOneListItem() == null)) {
            /** @var Entity\OneListItem || Entity\OneListItem[] $listItems */
            $listItems = $oneListItems->getOneListItem();

            // @codingStandardsIgnoreStart
            /** @var Entity\OneList $oneListRequest */
            $oneListRequest = (new Entity\OneList())
                ->setContactId($contactId)
                ->setCardId($cardId)
                ->setListType(Entity\Enum\ListType::BASKET)
                ->setItems($listItems)
                ->setStoreId($storeId);

            /** @var Entity\OneListCalculate $entity */
            if ($this->getCouponCode() != "" and $this->getCouponCode() != null) {
                $offer = new Entity\OneListPublishedOffer();
                $offers = new Entity\ArrayOfOneListPublishedOffer();
                $offers->setOneListPublishedOffer($offer);
                $offer->setId($this->getCouponCode());
                $offer->setType("Coupon");
                $oneListRequest->setPublishedOffers($offers);
            } else {
                $oneListRequest->setPublishedOffers($this->_offers());
            }

            $entity = new Entity\OneListCalculate();
            $entity->setOneList($oneListRequest);
            $request = new Operation\OneListCalculate();
            // @codingStandardsIgnoreEnd

            /** @var  Entity\OneListCalculateResponse $response */
            $response = $request->execute($entity);
        }
        if (($response == null)) {
            // @codingStandardsIgnoreLine
            $oneListCalResponse = new Entity\OneListCalculateResponse();

            return $oneListCalResponse->getResult();
        }
        if (property_exists($response, "OneListCalculateResult")) {
            // @codingStandardsIgnoreLine
            $this->setOneListCalculation($response->getResult());
            return $response->getResult();
        }
        if (is_object($response)) {
            $this->setOneListCalculation($response->getResult());
            return $response->getResult();
        } else {
            return $response;
        }
    }

    /**
     * @return mixed
     */
    public function getCouponCode()
    {
        $quoteCoupon = $this->checkoutSession->getCouponCode();
        if (!($quoteCoupon == null)) {
            $this->couponCode = $quoteCoupon;
            $this->setCouponQuote($quoteCoupon);
            return $quoteCoupon;
        } else {
            return $this->couponCode;
        }
    }

    /**
     * @param $couponCode
     */
    public function unsetCouponCode($couponCode)
    {
        $this->checkoutSession->setCouponCode($couponCode);
    }

    /**
     * TODO in next release.
     * Load Shipment Fee Product
     *
     */
    // @codingStandardsIgnoreLine
    public function getShipmentFeeProduct()
    {
    }

    /**
     * @return mixed
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function getOneListCalculation()
    {
        // @codingStandardsIgnoreStart
        $oneListCalc = $this->checkoutSession->getOneListCalculation();
        if ($oneListCalc == null) {
            $this->calculate($this->get());

            // calculate updates the session, so we fetch again
            return $this->checkoutSession->getOneListCalculation();
            // @codingStandardsIgnoreEnd
        }

        return $oneListCalc;
    }

    /**
     * @param Entity\OneListCalculateResponse|null $calculation
     */
    public function setOneListCalculation($calculation)
    {
        $this->checkoutSession->setOneListCalculation($calculation);
    }

    /**
     * @return array|bool|Entity\OneList|Entity\OneList[]|mixed|null
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    // @codingStandardsIgnoreLine
    public function get()
    {
        /** @var Entity\OneList $list */
        $list = null;

        //check if onelist is created and stored in session. if it is, than return it.
        if ($this->customerSession->getData(LSR::SESSION_CART_ONELIST)) {
            return $this->customerSession->getData(LSR::SESSION_CART_ONELIST);
        }

        /** @var Entity\MemberContact $loginContact */
        // For logged in users check if onelist is already stored in registry.
        if ($loginContact = $this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT)) {
            try {
                if ($loginContact->getBasket() instanceof Entity\OneList) {
                    $this->customerSession->setData(LSR::SESSION_CART_ONELIST, $loginContact->getBasket());
                    return $loginContact->getBasket();
                } else {
                    if ($loginContact->getBasket() instanceof Entity\ArrayOfOneList) {
                        foreach ($loginContact->getBasket()->getIterator() as $list) {
                            if ($list->getIsDefaultList()) {
                                $this->customerSession->setData(LSR::SESSION_CART_ONELIST, $list);
                                return $list;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }

        /** If no list found from customer session or registered user then get from omni */
        if ($list == null) {
            return $this->fetchFromOmni();
        }
        return null;
    }

    /**
     * @return array|bool|Entity\OneList|Entity\OneList[]|mixed
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function fetchFromOmni()
    {

        /** Handling the guest user too */
        $contactId = (!($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID) == null) ?
            $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID) : '');
        // if guest, then empty cardid
        $cardId = (!($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID) == null)
            ? $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID) : '');

        $store_id = $this->getDefaultWebStore();

        /**
         * If its a logged in user then we need to fetch already created one list.
         */

        if ($contactId != '') {
            /** @var Operation\OneListGetByContactId $request */
            // @codingStandardsIgnoreLine
            $request = new Operation\OneListGetByContactId();

            /** @var Entity\OneListGetByContactId $entity */
            // @codingStandardsIgnoreLine
            $entity = new Entity\OneListGetByContactId();

            $entity->setContactId($contactId)
                ->setListType(Entity\Enum\ListType::BASKET)
                ->setIncludeLines(true);

            /** @var Entity\OneListGetByContactIdResponse $response */
            $response = $request->execute($entity);

            $lists = $response->getOneListGetByContactIdResult()->getOneList();
            // if we have a list or an array, return it
            if (!empty($lists)) {
                if ($lists instanceof Entity\OneList) {
                    return $lists;
                } elseif (is_array($lists)) {
                    # return first list
                    return array_pop($lists);
                }
            }
        }

        /**
         * only those users who either does not have onelist created or
         * is guest user will come up here so for them lets create a new one.
         * for those lets create new list with no items and the existing offers and coupons
         */

        /** @var Entity\OneList $list */
        // @codingStandardsIgnoreStart
        $list = (new Entity\OneList())
            ->setContactId($contactId)
            ->setCardId($cardId)
            ->setDescription('OneList Magento')
            ->setIsDefaultList(true)
            ->setListType(Entity\Enum\ListType::BASKET)
            ->setItems(new Entity\ArrayOfOneListItem())
            ->setPublishedOffers($this->_offers())
            ->setStoreId($store_id);

        return $this->saveToOmni($list);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Unset onelist calculation from session.
     */
    public function unSetOneListCalculation()
    {
        $this->checkoutSession->unsOneListCalculation();
    }

    /**
     * @param $couponCode
     * @return Entity\OneListCalculateResponse|null|string
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setCouponCode($couponCode)
    {
        $status = "";
        $couponCode = trim($couponCode);
        if ($couponCode == "") {
            $this->couponCode = '';
            $this->update(
                $this->get()
            );
            $this->setCouponQuote("");

            return $status = '';
        }
        $this->couponCode = $couponCode;
        $status = $this->update(
            $this->get()
        );
        if (!is_object($status)) {
            $this->couponCode = '';
            $this->update(
                $this->get()
            );
            $this->setCouponQuote($this->couponCode);

            return $status;
        } elseif (!empty($status->getOrderDiscountLines()->getOrderDiscountLine())) {
            $status = "success";
            $this->setCouponQuote($this->couponCode);

            return $status;
        } else {
            $this->setCouponQuote("");
            return LSR::LS_COUPON_CODE_ERROR_MESSAGE;
        }
    }

    /**
     * @param $couponCode
     */
    public function setCouponQuote($couponCode)
    {
        $quote = $this->checkoutSession->getQuote();
        if (!empty($quote->getId())) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->setCouponCode($couponCode)->setTotalsCollectedFlag(false)->collectTotals();
            $quote->setCouponCode($couponCode);
            $this->checkoutSession->setCouponCode($couponCode);
        } else {
            $this->checkoutSession->setCouponCode("");
        }
        $this->quoteRepository->save($quote);
    }

    /**
     * Set Basket Session Data
     * @param $value
     */
    public function setBasketSessionValue($value)
    {
        $this->checkoutSession->setBasketdata($value);
    }

    /**
     * Get Basket Session Data
     * @return mixed
     */
    public function getBasketSessionValue()
    {
        return $this->checkoutSession->getBasketdata();
    }

    /**
     * Unset Basket Session Data
     * @return mixed
     */
    public function unSetBasketSessionValue()
    {
        return $this->checkoutSession->unsBasketdata();
    }
}