<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session\Proxy as CheckoutProxy;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session\Proxy as CustomerProxy;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * Useful helper functions for basket
 *
 */
class BasketHelper extends AbstractHelper
{
    /** @var Cart $cart */
    public $cart;

    /** @var ProductRepository $productRepository */
    public $productRepository;

    /**
     * @var CheckoutProxy
     */
    public $checkoutSession;

    /**
     * @var CustomerProxy
     */
    public $customerSession;

    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
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
     * @var boolean
     */
    public $calculateBasket;

    /**
     * @var $data
     */
    public $data;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    public $quoteResourceModel;

    /** @var CustomerFactory */
    public $customerFactory;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * BasketHelper constructor.
     * @param Context $context
     * @param Cart $cart
     * @param ProductRepository $productRepository
     * @param CheckoutProxy $checkoutSession
     * @param CustomerProxy $customerSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
     * @param ProductFactory $productFactory
     * @param ItemHelper $itemHelper
     * @param Registry $registry
     * @param LSR $Lsr
     * @param Data $data
     * @param SessionManagerInterface $session
     * @param CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel
     * @param CustomerFactory $customerFactory
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Context $context,
        Cart $cart,
        ProductRepository $productRepository,
        CheckoutProxy $checkoutSession,
        CustomerProxy $customerSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        ProductFactory $productFactory,
        ItemHelper $itemHelper,
        Registry $registry,
        LSR $Lsr,
        Data $data,
        SessionManagerInterface $session,
        CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResourceModel,
        CustomerFactory $customerFactory,
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct($context);
        $this->cart                           = $cart;
        $this->productRepository              = $productRepository;
        $this->checkoutSession                = $checkoutSession;
        $this->customerSession                = $customerSession;
        $this->searchCriteriaBuilder          = $searchCriteriaBuilder;
        $this->catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->productFactory                 = $productFactory;
        $this->itemHelper                     = $itemHelper;
        $this->registry                       = $registry;
        $this->lsr                            = $Lsr;
        $this->data                           = $data;
        $this->session                        = $session;
        $this->quoteRepository                = $quoteRepository;
        $this->quoteResourceModel             = $quoteResourceModel;
        $this->customerFactory                = $customerFactory;
        $this->cartRepository                 = $cartRepository;
        $this->calculateBasket                = $this->lsr->getPlaceToCalculateBasket();
    }

    /**
     * Compared a OneList with a quote and returns an array which contains
     * the items present only in the quote and only in the OneList (basket)
     * @param Entity\OneList $oneList
     * @param Quote $quote
     * @return array
     */
    public function compare(Entity\OneList $oneList, Quote $quote)
    {
        /** @var Entity\OneListItem[] $onlyInOneList */
        /** @var Entity\OneListItem[] $onlyInQuote */
        $onlyInOneList = [];
        $onlyInQuote   = [];

        /** @var Item[] $quoteItems */
        $cache      = [];
        $quoteItems = $quote->getAllVisibleItems();

        /** @var Entity\OneListItem[] $oneListItems */
        $oneListItems = !($oneList->getItems()->getOneListItem() == null)
            ? $oneList->getItems()->getOneListItem()
            : [];

        foreach ($oneListItems as $oneListItem) {
            $found = false;

            foreach ($quoteItems as $quoteItem) {
                $isConfigurable = $quoteItem->getProductType()
                    == Configurable::TYPE_CODE;
                if (isset($cache[$quoteItem->getId()]) || $isConfigurable) {
                    continue;
                }
                // @codingStandardsIgnoreStart
                $productLsrId = $this->productFactory->create()
                    ->load($quoteItem->getProduct()->getId())
                    ->getData('lsr_id');
                // @codingStandardsIgnoreEnd
                $quote_has_item = $productLsrId == $oneListItem->getItem()->getId();
                $qi_qty         = $quoteItem->getData('qty');
                $item_qty       = (int)($oneListItem->getQuantity());
                $match          = $quote_has_item && ($qi_qty == $item_qty);

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
                == Configurable::TYPE_CODE;

            // if the item is in the cache, it is present in the oneList and the quote
            if (isset($cache[$quoteItem->getId()]) || $isConfigurable) {
                continue;
            }
            $onlyInQuote[] = $quoteItem;
        }

        return [$onlyInQuote, $onlyInOneList];
    }

    /**
     * This function is overriding in hospitality module
     *
     * Populating items in the oneList from magneto quote
     * @param Quote $quote
     * @param Entity\OneList $oneList
     * @return Entity\OneList
     * @throws NoSuchEntityException
     */
    public function setOneListQuote(Quote $quote, Entity\OneList $oneList)
    {
        $quoteItems = $quote->getAllVisibleItems();

        // @codingStandardsIgnoreLine
        $items = new Entity\ArrayOfOneListItem();

        $itemsArray = [];

        foreach ($quoteItems as $quoteItem) {

            list($itemId, $variantId, $uom, $barCode) = $this->itemHelper->getComparisonValues(
                $quoteItem->getProductId(),
                $quoteItem->getSku()
            );

            // @codingStandardsIgnoreLine
            $list_item = (new Entity\OneListItem())
                ->setQuantity($quoteItem->getData('qty'))
                ->setItemId($itemId)
                ->setId('')
                ->setBarcodeId($barCode)
                ->setVariantId($variantId)
                ->setUnitOfMeasureId($uom);

            $itemsArray[] = $list_item;
        }
        $items->setOneListItem($itemsArray);

        $oneList->setItems($items)
            ->setPublishedOffers($this->_offers());

        $this->setOneListInCustomerSession($oneList);

        return $oneList;
    }

    /**
     * Get Order Lines Quote
     *
     * @param Quote $quote
     * @return Entity\ArrayOfOrderLine
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getOrderLinesQuote(Quote $quote)
    {
        // @codingStandardsIgnoreLine
        $orderLinesArray = new Entity\ArrayOfOrderLine();
        $quoteItems      = $quote->getAllVisibleItems();

        $itemsArray = [];

        foreach ($quoteItems as $quoteItem) {

            list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                $quoteItem->getProductId(),
                $quoteItem->getSku()
            );
            $priceIncTax = $discount = $discountPercentage = null;
            $product = $this->productRepository->get($quoteItem->getSku());
            $displayRegularPrice = $product->getPriceInfo()->getPrice(
                RegularPrice::PRICE_CODE
            )->getAmount()->getValue();
            $displayFinalPrice = $product->getPriceInfo()->getPrice(
                FinalPrice::PRICE_CODE
            )->getAmount()->getValue();

            if ($displayFinalPrice < $displayRegularPrice) {
                $priceIncTax = $displayRegularPrice;
                $discount = $displayRegularPrice - $displayFinalPrice;
                $discountPercentage = ($discount / $priceIncTax) * 100;
            }

            // @codingStandardsIgnoreLine
            $list_item = (new Entity\OrderLine())
                ->setValidateTax(1)
                ->setQuantity($quoteItem->getData('qty'))
                ->setItemId($itemId)
                ->setId('')
                ->setVariantId($variantId)
                ->setUomId($uom)
                ->setLineType(Entity\Enum\LineType::ITEM)
                ->setAmount($quoteItem->getRowTotalInclTax())
                ->setNetAmount($quoteItem->getRowTotal())
                ->setPrice($priceIncTax ?? $quoteItem->getPriceInclTax())
                ->setNetPrice($quoteItem->getPrice())
                ->setTaxAmount($quoteItem->getTaxAmount())
                ->setDiscountAmount($quoteItem->getDiscountAmount())
                ->setDiscountPercent($discountPercentage);

            $itemsArray[] = $list_item;
        }
        $orderLinesArray->setOrderLine($itemsArray);

        return $orderLinesArray;
    }

    /**
     * @return Entity\ArrayOfOneListPublishedOffer
     */
    public function _offers()
    {
        // @codingStandardsIgnoreLine
        return new Entity\ArrayOfOneListPublishedOffer();
    }

    /**
     * Generating commerce services wishlist from magento wishlist
     *
     * @param Entity\OneList $oneList
     * @param $wishlistItems
     * @return Entity\OneList
     */
    public function addProductToExistingWishlist(Entity\OneList $oneList, $wishlistItems)
    {
        // @codingStandardsIgnoreLine
        $items      = new Entity\ArrayOfOneListItem();
        $itemsArray = [];

        foreach ($wishlistItems as $item) {
            if ($item->getOptionByCode('simple_product')) {
                $product = $item->getOptionByCode('simple_product')->getProduct();
            } else {
                $product = $item->getProduct();
            }
            list($itemId, $variantId, $uom, $barCode) = $this->itemHelper->getComparisonValues(
                $product->getId(),
                $product->getSku()
            );
            $qty = $item->getData('qty');
            // @codingStandardsIgnoreLine
            $list_item = (new Entity\OneListItem())
                ->setQuantity($qty)
                ->setItemId($itemId)
                ->setId('')
                ->setBarcodeId($barCode)
                ->setVariantId($variantId)
                ->setUnitOfMeasureId($uom);

            $itemsArray[] = $list_item;
        }
        $items->setOneListItem($itemsArray);
        $oneList->setItems($items);

        return $oneList;
    }

    /**
     * @param Entity\OneList $oneList
     * @return bool
     */
    public function delete(Entity\OneList $oneList)
    {
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
     * @return bool|Entity\OneList
     * @throws NoSuchEntityException
     */
    // @codingStandardsIgnoreLine

    public function updateWishlistAtOmni(Entity\OneList $oneList)
    {
        return $this->saveWishlistToOmni($oneList);
    }

    /**
     * @param Entity\OneList $list
     * @return bool|Entity\OneList
     * @throws NoSuchEntityException
     */
    public function saveWishlistToOmni(Entity\OneList $list)
    {
        // @codingStandardsIgnoreLine
        $operation = new Operation\OneListSave();

        $list->setStoreId($this->getDefaultWebStore());

        // @codingStandardsIgnoreLine
        $request = (new Entity\OneListSave())
            ->setOneList($list)
            ->setCalculate(true);

        /** @var Entity\OneListSaveResponse $response */
        $response = $operation->execute($request);
        if ($response) {
            $this->setWishListInCustomerSession($response->getOneListSaveResult());
            return $response->getOneListSaveResult();
        }
        return false;
    }

    /**
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getDefaultWebStore()
    {
        if ($this->store_id == null) {
            $this->store_id = $this->lsr->getActiveWebStore();
        }

        return $this->store_id;
    }

    /**
     * @param Entity\OneList $oneList
     * @return bool|Entity\OrderAvailabilityResponse|Entity\OrderCheckAvailabilityResponse|ResponseInterface
     * @throws NoSuchEntityException
     */
    public function availability(Entity\OneList $oneList)
    {
        $oneListItems = $oneList->getItems();
        $response     = false;

        if (!($oneListItems->getOneListItem() == null)) {
            $array = [];

            $count = 1;

            foreach ($oneListItems->getOneListItem() as $listItem) {
                $variant = $listItem->getVariant();
                $uom     = !($listItem->getUom() == null) ? $listItem->getUom()[0]->getId() : null;
                // @codingStandardsIgnoreLine
                $line    = (new Entity\OrderLineAvailability())
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
            $entity  = new Entity\OrderCheckAvailability();
            $entity->setRequest($request);
            $operation = new Operation\OrderCheckAvailability();
            // @codingStandardsIgnoreEnd
            $response = $operation->execute($entity);
        }

        return $response ? $response->getOrderCheckAvailabilityResult() : $response;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getCouponCode()
    {
        $quoteCoupon = $this->cart->getQuote()->getCouponCode();

        if (!($quoteCoupon == null)) {
            $this->couponCode = $quoteCoupon;
        }

        return $this->couponCode;
    }

    /**
     * Send coupon code to basket calculation
     * @param $couponCode
     * @return Entity\OneListCalculateResponse|Phrase|string|null
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     */
    public function setCouponCode($couponCode)
    {
        $status     = "";
        $couponCode = trim($couponCode);
        if ($couponCode == "") {
            $this->couponCode = '';
            $this->setCouponQuote("");
            $this->update(
                $this->get()
            );
            $this->itemHelper->setDiscountedPricesForItems(
                $this->checkoutSession->getQuote(),
                $this->getBasketSessionValue()
            );

            return $status = '';
        }
        $this->couponCode = $couponCode;
        $status           = $this->update(
            $this->get()
        );

        $checkCouponAmount = $this->data->orderBalanceCheck(
            $this->checkoutSession->getQuote()->getLsGiftCardNo(),
            $this->checkoutSession->getQuote()->getLsGiftCardAmountUsed(),
            $this->checkoutSession->getQuote()->getLsPointsSpent(),
            $status,
            false
        );

        if (!is_object($status) || $checkCouponAmount) {
            $this->couponCode = '';
            $this->update(
                $this->get()
            );
            $this->setCouponQuote($this->couponCode);
            $status = __("Coupon Code is not valid");
            if ($checkCouponAmount) {
                $status = $checkCouponAmount;
            }
            return $status;
        } elseif (!empty($status->getOrderDiscountLines()->getOrderDiscountLine())) {
            if (is_array($status->getOrderDiscountLines()->getOrderDiscountLine())) {
                foreach ($status->getOrderDiscountLines()->getOrderDiscountLine() as $orderDiscountLine) {
                    if ($orderDiscountLine->getDiscountType() == 'Coupon') {
                        $status = "success";
                        $this->itemHelper->setDiscountedPricesForItems(
                            $this->checkoutSession->getQuote(),
                            $this->getBasketSessionValue()
                        );
                        $this->setCouponQuote($this->couponCode);
                    }
                }
            } else {
                if ($status->getOrderDiscountLines()->getOrderDiscountLine()->getDiscountType() == 'Coupon') {
                    $status = "success";
                    $this->itemHelper->setDiscountedPricesForItems(
                        $this->checkoutSession->getQuote(),
                        $this->getBasketSessionValue()
                    );
                    $this->setCouponQuote($this->couponCode);
                }
            }
            if (is_object($status)) {
                $status = __("Coupon Code is not valid for these item(s)");
            }

            return $status;
        } else {
            $this->setCouponQuote("");
            return __("Coupon Code is not valid for these item(s)");
        }
    }

    /**
     * @param $couponCode
     * @throws Exception
     */
    public function setCouponQuote($couponCode)
    {
        $cartQuote = $this->cart->getQuote();
        if (!empty($cartQuote->getId())) {
            $cartQuote->getShippingAddress()->setCollectShippingRates(true);
            $cartQuote->setCouponCode($couponCode);
            $cartQuote->collectTotals();
        }
        $this->quoteResourceModel->save($cartQuote);
    }

    /**
     * @param Entity\OneList $oneList
     * @return Entity\OneListCalculateResponse|Order
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function update(Entity\OneList $oneList)
    {
        return $this->calculate($oneList);
    }

    /**
     * @param Entity\OneList $list
     * @return bool|Entity\OneList
     * @throws NoSuchEntityException
     */
    public function saveToOmni(Entity\OneList $list)
    {
        // @codingStandardsIgnoreLine
        $operation = new Operation\OneListSave();
        $list->setStoreId($this->getDefaultWebStore());

        if (version_compare($this->lsr->getOmniVersion(), '4.19', '>')) {
            $list->setSalesType(LSR::SALE_TYPE_POS);
        }

        // @codingStandardsIgnoreLine
        $request = (new Entity\OneListSave())
            ->setOneList($list)
            ->setCalculate(true);
        /** @var Entity\OneListSaveResponse $response */
        $response = $operation->execute($request);
        if ($response) {
            $this->setOneListInCustomerSession($response->getOneListSaveResult());
            return $response->getOneListSaveResult();
        }
        return false;
    }

    /**
     * This function is overriding in hospitality module
     * @param Entity\OneList $oneList
     * @return Entity\OneListCalculateResponse|Entity\Order
     * @throws InvalidEnumException|NoSuchEntityException
     * @throws Exception
     */
    public function calculate(Entity\OneList $oneList)
    {
        if (empty($this->getCouponCode()) && $this->calculateBasket == 1
            && empty($this->getOneListCalculationFromCheckoutSession())) {
            return null;
        }

        $storeId = $this->getDefaultWebStore();
        $cardId  = $oneList->getCardId();

        /** @var Entity\ArrayOfOneListItem $oneListItems */
        $oneListItems = $oneList->getItems();

        /** @var Entity\OneListCalculateResponse $response */
        $response = false;

        if (!($oneListItems->getOneListItem() == null)) {
            /** @var Entity\OneListItem || Entity\OneListItem[] $listItems */
            $listItems = $oneListItems->getOneListItem();

            if (!is_array($listItems)) {
                /** Entity\ArrayOfOneListItem $items */
                // @codingStandardsIgnoreLine
                $items = new Entity\ArrayOfOneListItem();
                $items->setOneListItem($listItems);
                $listItems = $items;
            }
            // @codingStandardsIgnoreStart
            $oneListRequest = (new Entity\OneList())
                ->setCardId($cardId)
                ->setListType(Entity\Enum\ListType::BASKET)
                ->setItems($listItems)
                ->setStoreId($storeId);

            if (version_compare($this->lsr->getOmniVersion(), '4.19', '>')) {
                $oneListRequest
                    ->setIsHospitality(false)
                    ->setSalesType(LSR::SALE_TYPE_POS);
            }

            if (version_compare($this->lsr->getOmniVersion(), '4.24', '>')) {
                $oneListRequest->setShipToCountryCode($oneList->getShipToCountryCode());
            }

            /** @var Entity\OneListCalculate $entity */
            if ($this->getCouponCode() != "" and $this->getCouponCode() != null) {
                $offer  = new Entity\OneListPublishedOffer();
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
            $this->setOneListCalculationInCheckoutSession($response->getResult());
            return $response->getResult();
        }
        if (is_object($response)) {
            $this->setOneListCalculationInCheckoutSession($response->getResult());
            return $response->getResult();
        } else {
            return $response;
        }
    }

    /**
     * Create a new oneList for syncing from admin/cron
     *
     * @param $customerEmail
     * @param $websiteId
     * @param $isGuest
     * @return bool|Entity\OneList
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getOneListAdmin($customerEmail, $websiteId, $isGuest)
    {
        $list   = null;
        $cardId = '';

        if (!$isGuest) {
            $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($customerEmail);

            if (!empty($customer->getData('lsr_cardid'))) {
                $cardId = $customer->getData('lsr_cardid');
            }
        }
        $webStore       = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $websiteId);
        $this->store_id = $webStore;
        // @codingStandardsIgnoreStart
        /** @var Entity\OneList $list */
        $list = (new Entity\OneList())
            ->setCardId($cardId)
            ->setDescription('OneList Magento')
            ->setListType(Entity\Enum\ListType::BASKET)
            ->setItems(new Entity\ArrayOfOneListItem())
            ->setPublishedOffers($this->_offers())
            ->setStoreId($webStore);

        return $this->saveToOmni($list);
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param null $id
     * @return array|bool|Entity\OneList|Entity\OneList[]|mixed|null
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function get($id = null)
    {
        /** @var Entity\OneList $list */
        $list = null;

        //check if onelist is created and stored in session. if it is, than return it.
        if ($this->getOneListFromCustomerSession()) {
            if ($id) {
                if ($id == $this->getOneListFromCustomerSession()->getId()) {
                    return $this->getOneListFromCustomerSession();
                }
            } else {
                return $this->getOneListFromCustomerSession();
            }
        }

        /** @var Entity\MemberContact $loginContact */
        // For logged in users check if onelist is already stored in registry.
        if ($loginContact = $this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT)) {
            try {
                if ($loginContact->getOneLists()->getOneList() instanceof Entity\OneList) {
                    $this->setOneListInCustomerSession($loginContact->getOneLists()->getOneList());
                    return $loginContact->getOneLists()->getOneList();
                } else {
                    if ($loginContact->getOneLists() instanceof Entity\ArrayOfOneList) {
                        foreach ($loginContact->getOneLists()->getOneList() as $oneList) {
                            if ($oneList->getListType() == Entity\Enum\ListType::BASKET
                                && $oneList->getStoreId() == $this->getDefaultWebStore()
                            ) {
                                $this->setOneListInCustomerSession($oneList);

                                return $oneList;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $this->_logger->critical($e);
            }
        }
        if ($id) {
            $entity = new Entity\OneListGetById();
            $entity->setId($id);
            $entity->setIncludeLines(true);
            $request  = new Operation\OneListGetById();
            $response = $request->execute($entity);
            return $response->getOneListGetByIdResult();
        }

        /** If no list found from customer session or registered user then get from omni */
        if ($list == null) {
            return $this->fetchFromOmni();
        }
        return null;
    }

    /**
     * @return array|bool|Entity\OneList|Entity\OneList[]|mixed
     * @throws InvalidEnumException|NoSuchEntityException
     */
    public function fetchFromOmni()
    {
        // if guest, then empty card id
        $cardId = (!($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID) == null)
            ? $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID) : '');

        $store_id = $this->getDefaultWebStore();

        /**
         * only those users who either does not have onelist created or
         * is guest user will come up here so for them lets create a new one.
         * for those lets create new list with no items and the existing offers and coupons
         */

        // @codingStandardsIgnoreStart
        $list = (new Entity\OneList())
            ->setCardId($cardId)
            ->setDescription('OneList Magento')
            ->setListType(Entity\Enum\ListType::BASKET)
            ->setItems(new Entity\ArrayOfOneListItem())
            ->setPublishedOffers($this->_offers())
            ->setStoreId($store_id);
        // @codingStandardsIgnoreEnd

        if (version_compare($this->lsr->getOmniVersion(), '4.19', '>')) {
            $list->setSalesType(LSR::SALE_TYPE_POS);
        }

        return $list;
    }

    /**
     * @return Entity\OneList|mixed
     * @throws InvalidEnumException|NoSuchEntityException
     */
    public function fetchCurrentCustomerWishlist()
    {
        //check if onelist is created and stored in session. if it is, than return it.
        if ($this->getWishListFromCustomerSession()) {
            return $this->getWishListFromCustomerSession();
        }
        $cardId = (!($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID) == null)
            ? $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID) : '');

        $store_id = $this->getDefaultWebStore();
        return (new Entity\OneList())
            ->setCardId($cardId)
            ->setDescription('List ' . $cardId)
            ->setListType(Entity\Enum\ListType::WISH)
            ->setItems(new Entity\ArrayOfOneListItem())
            ->setStoreId($store_id);
    }

    /**
     * Return Item Helper which can be used on multiple areas where we have dependency injection issue.
     */
    public function getItemHelper()
    {
        return $this->itemHelper;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Get Correct Item Row Total for mini-cart after comparison
     *
     * @param $item
     * @return string
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getItemRowTotal($item)
    {
        $baseUnitOfMeasure = $item->getProduct()->getData('uom');
        list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
            $item->getProductId(),
            $item->getSku()
        );
        $rowTotal   = $item->getRowTotal();
        $basketData = $this->getOneListCalculation();
        $orderLines = $basketData ? $basketData->getOrderLines()->getOrderLine() : [];

        foreach ($orderLines as $line) {
            if ($this->itemHelper->isValid($line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                $rowTotal = $line->getAmount();
                break;
            }
        }

        return $rowTotal;
    }

    /**
     * @return mixed
     * @throws InvalidEnumException|NoSuchEntityException
     */
    public function getOneListCalculation()
    {
        // @codingStandardsIgnoreStart
        $oneListCalc = $this->getOneListCalculationFromCheckoutSession();

        if ($oneListCalc == null && $this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $this->calculate($this->get());

            // calculate updates the session, so we fetch again
            return $this->getOneListCalculationFromCheckoutSession();
            // @codingStandardsIgnoreEnd
        }

        return $oneListCalc;
    }

    /**
     * Calculate oneList to sync order from admin/cron
     *
     * @param $order
     * @return Order
     * @throws InvalidEnumException
     * @throws LocalizedException
     */
    public function calculateOneListFromOrder($order)
    {
        $couponCode = $order->getCouponCode();
        $quote      = $this->cartRepository->get($order->getQuoteId());
        $oneList    = $this->getOneListAdmin(
            $order->getCustomerEmail(),
            $order->getStore()->getWebsiteId(),
            $order->getCustomerIsGuest()
        );
        $oneList    = $this->setOneListQuote($quote, $oneList);
        $this->setCouponCodeInAdmin($couponCode);

        return $this->update($oneList);
    }

    /**
     * This function is overriding in hospitality module
     *
     * Formulate Central order request given Magento order
     *
     * @param $order
     * @return Order
     * @throws InvalidEnumException
     * @throws LocalizedException
     */
    public function formulateCentralOrderRequestFromMagentoOrder($order)
    {
        // @codingStandardsIgnoreLine
        $orderEntity   = new Entity\Order();
        $quote         = $this->cartRepository->get($order->getQuoteId());
        $websiteId     = $order->getStore()->getWebsiteId();
        $customerEmail = $order->getCustomerEmail();
        $webStore      = $this->lsr->getWebsiteConfig(
            LSR::SC_SERVICE_STORE,
            $websiteId
        );
        $orderEntity->setStoreId($webStore);

        if (!$order->getCustomerIsGuest()) {
            $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($customerEmail);

            if (!empty($customer->getData('lsr_cardid'))) {
                $orderEntity->setCardId($customer->getData('lsr_cardid'));
            }
        }
        $orderLinesArray = $this->getOrderLinesQuote($quote);
        $orderEntity->setOrderLines($orderLinesArray);

        return $orderEntity;
    }

    /**
     * Sending request to Central for basket calculation
     *
     * @param $cartId
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function syncBasketWithCentral($cartId)
    {
        $oneList = $this->getOneListFromCustomerSession();
        $quote   = $this->quoteRepository->getActive($cartId);

        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) && $oneList && $this->getCalculateBasket()) {
            $this->setCalculateBasket(false);
            $this->updateBasketAndSaveTotals($oneList, $quote);
            $this->setCalculateBasket(true);
        }
    }

    /**
     * Updating basket from Central and storing response
     *
     * @param $oneList
     * @param $quote
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateBasketAndSaveTotals($oneList, $quote)
    {
        if (version_compare($this->lsr->getOmniVersion(), '4.24', '>')) {
            $country = $quote->getShippingAddress()->getCountryId();
            $oneList->setShipToCountryCode($country);
        }

        $basketData = $this->update($oneList);
        $this->itemHelper->setDiscountedPricesForItems($quote, $basketData);

        if ($this->checkoutSession->getQuote()->getLsGiftCardAmountUsed() > 0 ||
            $this->checkoutSession->getQuote()->getLsPointsSpent() > 0) {
            $this->data->orderBalanceCheck(
                $this->checkoutSession->getQuote()->getLsGiftCardNo(),
                $this->checkoutSession->getQuote()->getLsGiftCardAmountUsed(),
                $this->checkoutSession->getQuote()->getLsPointsSpent(),
                $basketData
            );
        }
    }

    /**
     * Get Basket Session Data
     * @return mixed
     */
    public function getBasketSessionValue()
    {
        return $this->getOneListCalculationFromCheckoutSession();
    }

    /**
     * @param $oneList
     */
    public function setOneListInCustomerSession($oneList)
    {
        $this->customerSession->setData(LSR::SESSION_CART_ONELIST, $oneList);
    }

    /**
     * @return mixed|null
     */
    public function getOneListFromCustomerSession()
    {
        return $this->customerSession->getData(LSR::SESSION_CART_ONELIST);
    }

    /**
     * @param $wishList
     */
    public function setWishListInCustomerSession($wishList)
    {
        $this->customerSession->setData(LSR::SESSION_CART_WISHLIST, $wishList);
    }

    /**
     * @return mixed|null
     */
    public function getWishListFromCustomerSession()
    {
        return $this->customerSession->getData(LSR::SESSION_CART_WISHLIST);
    }

    /**
     * @param Entity\OneListCalculateResponse|null $calculation
     */
    public function setOneListCalculationInCheckoutSession($calculation)
    {
        $this->checkoutSession->setData(LSR::SESSION_CHECKOUT_ONE_LIST_CALCULATION, $calculation);
    }

    /**
     * @return mixed|null
     */
    public function getOneListCalculationFromCheckoutSession()
    {
        return $this->checkoutSession->getData(LSR::SESSION_CHECKOUT_ONE_LIST_CALCULATION);
    }

    /**
     * @param $memberPoints
     */
    public function setMemberPointsInCheckoutSession($memberPoints)
    {
        $this->checkoutSession->setData(LSR::SESSION_CHECKOUT_MEMBERPOINTS, $memberPoints);
    }

    /**
     * @return mixed|null
     */
    public function getMemberPointsFromCheckoutSession()
    {
        return $this->checkoutSession->getData(LSR::SESSION_CHECKOUT_MEMBERPOINTS);
    }

    /**
     * @param $documentId
     */
    public function setLastDocumentIdInCheckoutSession($documentId)
    {
        $this->checkoutSession->setData(LSR::SESSION_CHECKOUT_LAST_DOCUMENT_ID, $documentId);
    }

    /**
     * @return mixed|null
     */
    public function getLastDocumentIdFromCheckoutSession()
    {
        return $this->checkoutSession->getData(LSR::SESSION_CHECKOUT_LAST_DOCUMENT_ID);
    }

    /**
     * Set correct_store_id in checkout session being used in case of admin
     *
     * @param $storeId
     */
    public function setCorrectStoreIdInCheckoutSession($storeId)
    {
        $this->checkoutSession->setData(LSR::SESSION_CHECKOUT_CORRECT_STORE_ID, $storeId);
    }

    /**
     * Get correct_store_id from checkout session being used in case of admin
     *
     * @return mixed|null
     */
    public function getCorrectStoreIdFromCheckoutSession()
    {
        return $this->checkoutSession->getData(LSR::SESSION_CHECKOUT_CORRECT_STORE_ID);
    }

    /**
     * clear correct_store_id from checkout session being used in case of admin
     */
    public function unSetCorrectStoreId()
    {
        $this->checkoutSession->unsetData(LSR::SESSION_CHECKOUT_CORRECT_STORE_ID);
    }

    /**
     * clear one list calculation from checkout session
     */
    public function unSetOneListCalculation()
    {
        $this->checkoutSession->unsetData(LSR::SESSION_CHECKOUT_ONE_LIST_CALCULATION);
    }

    /**
     * clear onelist from customer session
     */
    public function unSetOneList()
    {
        $this->customerSession->unsetData(LSR::SESSION_CART_ONELIST);
    }

    /**
     * clear member points from checkout session
     */
    public function unSetMemberPoints()
    {
        $this->checkoutSession->unsetData(LSR::SESSION_CHECKOUT_MEMBERPOINTS);
    }

    /**
     * clear last document id from checkout session
     */
    public function unSetLastDocumentId()
    {
        $this->checkoutSession->unsetData(LSR::SESSION_CHECKOUT_LAST_DOCUMENT_ID);
    }

    /**
     * clear quote_id from checkout session
     */
    public function unSetQuoteId()
    {
        $this->checkoutSession->setQuoteId(null);
    }

    /**
     * clear required data from customer and checkout sessions
     */
    public function unSetRequiredDataFromCustomerAndCheckoutSessions()
    {
        $this->unSetMemberPoints();
        $this->unSetOneList();
        $this->unSetOneListCalculation();
        $this->unSetCorrectStoreId();
        $this->unSetQuoteId();
    }

    /**
     * @param $couponCode
     */
    public function setCouponCodeInAdmin($couponCode)
    {
        $this->couponCode = $couponCode;
    }

    /**
     * Setting value in calculateBasket
     *
     * @param $value
     */
    public function setCalculateBasket($value)
    {
        $this->calculateBasket = $value;
    }

    /**
     * Getting value of calculateBasket
     *
     * @return bool|mixed
     */
    public function getCalculateBasket()
    {
        return $this->calculateBasket;
    }

    /**
     * Search criteria builder function can be used in another class
     *
     * @return SearchCriteriaBuilder
     */
    public function getSearchCriteriaBuilder()
    {
        return $this->searchCriteriaBuilder;
    }

    /**
     * Get Repository
     *
     * @return CartRepositoryInterface
     */
    public function getQuoteRepository()
    {
        return $this->quoteRepository;
    }

    /**
     * Get lsr model
     *
     * @return LSR
     */
    public function getLsrModel()
    {
        return $this->lsr;
    }
}
