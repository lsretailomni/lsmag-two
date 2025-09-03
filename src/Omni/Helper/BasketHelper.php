<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\MobileTransaction;
use \Ls\Omni\Client\Ecommerce\Entity\MobileTransactionLine;
use \Ls\Omni\Client\Ecommerce\Entity\MobileTransDiscountLine;
use \Ls\Omni\Client\Ecommerce\Entity\Order;
use \Ls\Omni\Client\Ecommerce\Entity\RootMobileTransaction;
use \Ls\Omni\Client\Ecommerce\Operation\EcomCalculateBasket;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * Useful helper functions for basket
 *
 */
class BasketHelper extends AbstractHelperOmni
{
    /** @var null|string */
    public $storeId = null;

    /** @var string */
    public ?string $couponCode = '';

    /**
     * @var boolean
     */
    public $calculateBasket;

    /*
     * @var string
     */
    public $adminOrderCardId = "";

    /**
     * Initialize specific properties
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->calculateBasket = $this->lsr->getPlaceToCalculateBasket();
    }

    /**
     * This function is overriding in hospitality module
     *
     * Populating items in the oneList from magneto quote
     *
     * @param Quote $quote
     * @param RootMobileTransaction $oneList
     * @return RootMobileTransaction
     * @throws NoSuchEntityException|LocalizedException
     */
    public function setOneListQuote(Quote $quote, RootMobileTransaction $oneList)
    {
        $quoteItems = $quote->getAllVisibleItems();

        return $this->setGivenItemsInGivenOneList($oneList, $quoteItems);
    }

    /**
     * Set given items in given one list
     *
     * @param RootMobileTransaction $oneList
     * @param array $items
     * @return RootMobileTransaction
     * @throws NoSuchEntityException|LocalizedException
     */
    public function setGivenItemsInGivenOneList(RootMobileTransaction $oneList, array $items)
    {
        $itemsArray = [];

        $transactionId = $oneList->getMobiletransaction()
            ->getId();
        $storeCode = $this->getDefaultWebStore();
        $lineNumber = 0;
        foreach ($items as $quoteItem) {
            $children = [];
            $isBundle = 0;
            if ($quoteItem->getProductType() == Type::TYPE_BUNDLE) {
                $children = $quoteItem->getChildren();
                $isBundle = 1;
            } else {
                $children[] = $quoteItem;
            }

            foreach ($children as $child) {
                if ($child->getProduct()->isInStock()) {
                    $lineNumber += 10000;
                    list($itemId, $variantId, $uom, $barCode) =
                        $this->itemHelper->getItemAttributesGivenQuoteItem($child);
                    $match = false;
                    $giftCardIdentifier = $this->lsr->getGiftCardIdentifiers();

                    if (in_array($itemId, explode(',', $giftCardIdentifier))) {
                        foreach ($itemsArray as $itemArray) {
                            if ($itemArray->getId() == $child->getItemId()) {
                                $itemArray->setQuantity($itemArray->getQuantity() + $quoteItem->getData('qty'));
                                $match = true;
                                break;
                            }
                        }
                    } else {
                        foreach ($itemsArray as $itemArray) {
                            if (is_numeric($itemArray->getId()) ?
                                $itemArray->getId() == $child->getItemId() :
                                ($itemArray->getItemId() == $itemId &&
                                    $itemArray->getVariantId() == $variantId &&
                                    $itemArray->getUnitOfMeasureId() == $uom &&
                                    $itemArray->getBarcodeId() == $barCode)
                            ) {
                                $itemArray->setQuantity($itemArray->getQuantity() + $quoteItem->getData('qty'));
                                $match = true;
                                break;
                            }
                        }
                    }

                    if (!$match) {
                        $price = ($quoteItem->getProductType() == LSR::TYPE_GIFT_CARD) ?
                            $quoteItem->getPrice() :
                            $quoteItem->getProduct()->getPrice();
                        $price = $this->itemHelper->convertToCurrentStoreCurrency($price);
                        $qty = $isBundle ? $child->getData('qty') * $quoteItem->getData('qty') :
                            $quoteItem->getData('qty');
                        $amount = $this->itemHelper->convertToCurrentStoreCurrency($quoteItem->getPrice() * $qty);
                        // @codingStandardsIgnoreLine
                        $listItem = $this->createInstance(
                            MobileTransactionLine::class,
                            [
                                'data' => [
                                    MobileTransactionLine::ID => $transactionId,
                                    MobileTransactionLine::LINE_NO => $lineNumber,
                                    MobileTransactionLine::STORE_ID => $storeCode,
                                    MobileTransactionLine::QUANTITY => $qty,
                                    MobileTransactionLine::NUMBER => $itemId,
                                    MobileTransactionLine::VARIANT_CODE => $variantId,
                                    MobileTransactionLine::UOM_ID => $uom,
                                    MobileTransactionLine::PRICE => $price,
                                    MobileTransactionLine::NET_AMOUNT => $amount,
                                    MobileTransactionLine::TRANS_DATE => $this->getCompatibleDateTime(),
                                    MobileTransactionLine::CURRENCY_FACTOR => 1
                                ]
                            ]
                        );
                        $itemsArray[] = $listItem;
                    }
                }
            }
        }
        $oneList->setMobiletransactionline($itemsArray);

        return $oneList;
    }

    /**
     * Generating commerce services wishlist from magento wishlist
     *
     * @param RootMobileTransaction $oneList
     * @param array $wishlistItems
     * @return RootMobileTransaction
     * @throws NoSuchEntityException
     */
    public function addProductToExistingWishlist(RootMobileTransaction $oneList, array $wishlistItems)
    {
        $transactionId = $oneList->getMobiletransaction()
            ->getId();
        $itemsArray = [];
        $storeCode = $this->getDefaultWebStore();
        foreach ($wishlistItems as $lineNumber => $item) {
            $lineNumber = (++$lineNumber) * 10000;
            if ($item->getOptionByCode('simple_product')) {
                $product = $item->getOptionByCode('simple_product')->getProduct();
            } else {
                $product = $item->getProduct();
            }
            list($itemId, $variantId, $uom, $barCode) = $this->itemHelper->getComparisonValues(
                $product->getSku()
            );
            $qty = $item->getData('qty');
            $listItem = $this->createInstance(
                MobileTransactionLine::class,
                [
                    'data' => [
                        MobileTransactionLine::ID => $transactionId,
                        MobileTransactionLine::LINE_NO => $lineNumber,
                        MobileTransactionLine::STORE_ID => $storeCode,
                        MobileTransactionLine::QUANTITY => $qty,
                        MobileTransactionLine::NUMBER => $itemId,
                        MobileTransactionLine::VARIANT_CODE => $variantId,
                        MobileTransactionLine::UOM_ID => $uom,
                        MobileTransactionLine::TRANS_DATE => $this->getCompatibleDateTime(),
                        MobileTransactionLine::CURRENCY_FACTOR => 1
                    ]
                ]
            );

            $itemsArray[] = $listItem;
        }
        $oneList->setMobiletransactionline($itemsArray);

        return $oneList;
    }

    /**
     * Get Order Lines and Discount Lines
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     * @throws InvalidEnumException
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getOrderLinesQuote(\Magento\Sales\Model\Order $order)
    {
        $quote = $this->cartRepository->get($order->getQuoteId());
        $websiteId = $quote->getStore()->getWebsiteId();
        $storeCode = $this->lsr->getWebsiteConfig(
            LSR::SC_SERVICE_STORE,
            $websiteId
        );
        $customerEmail = $order->getCustomerEmail();
        $basketResponse = $quote->getBasketResponse();
        $mobileTransaction = $mobileTransactionLines = $mobileTransactionDiscountLines = [];

        if (!empty($basketResponse)) {
            // phpcs:ignore Magento2.Security.InsecureFunction.FoundWithAlternative
            $basketData = $this->restoreModel(unserialize($basketResponse));
            $mobileTransaction = $basketData->getMobiletransaction();
            $mobileTransactionDiscountLines = $basketData->getMobiletransdiscountline();
            $mobileTransactionLines = $basketData->getMobiletransactionline();
        }

        $quoteItems = $quote->getAllVisibleItems();

        if (empty($mobileTransaction)) {
            $mobileTransaction[] = $this->createInstance(MobileTransaction::class);
            current($mobileTransaction)->addData([
                MobileTransaction::STORE_ID => $storeCode,
            ]);
        }

        if (!$order->getCustomerIsGuest()) {
            $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($customerEmail);

            if (empty($customer->getData('lsr_cardid'))) {
                $this->contactHelper->syncCustomerAndAddress($customer);
                $customer = $this->contactHelper->loadCustomerByEmailAndWebsiteId($customerEmail, $websiteId);
            }

            current($mobileTransaction)->addData([
                MobileTransaction::MEMBER_CARD_NO => $customer->getData('lsr_cardid'),
            ]);
        }

        if (empty($mobileTransactionLines)) {
            $lineNumber = 10000;

            foreach ($quoteItems as $quoteItem) {
                list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                    $quoteItem->getSku()
                );
                $priceIncTax = $discountPercentage = $discount = null;
                $regularPrice = $quoteItem->getOriginalPrice();
                $finalPrice = $quoteItem->getPriceInclTax();

                if ($finalPrice < $regularPrice) {
                    $priceIncTax = $regularPrice;
                    $discount = ($regularPrice - $finalPrice) * $quoteItem->getData('qty');
                    $discountPercentage = (($regularPrice - $finalPrice) / $regularPrice) * 100;
                }

                if ($quoteItem->getDiscountAmount() > 0) {
                    if (!$discount && !$discountPercentage) {
                        $discount = $quoteItem->getDiscountAmount();
                        $discountPercentage = $quoteItem->getDiscountPercent();

                        if ($discountPercentage == 0) {
                            $rowTotalInclTax = $quoteItem->getRowTotalInclTax();
                            $discountPercentage = ($discount / $rowTotalInclTax) * 100;
                        }
                    } else {
                        $rowTotalInclTax = $quoteItem->getRowTotalInclTax() + $discount;
                        $discount += $quoteItem->getDiscountAmount();
                        $discountPercentage = ($discount / $rowTotalInclTax) * 100;
                    }
                }

                $orderLine = $this->createInstance(MobileTransactionLine::class);
                $orderLine->addData([
                    MobileTransactionLine::LINE_NO => $lineNumber,
                    MobileTransactionLine::LINE_TYPE => 0,
                    MobileTransactionLine::STORE_ID => $storeCode,
                    MobileTransactionLine::QUANTITY => $quoteItem->getData('qty'),
                    MobileTransactionLine::NUMBER => $itemId,
                    MobileTransactionLine::VARIANT_CODE => $variantId,
                    MobileTransactionLine::UOM_ID => $uom,
                    MobileTransactionLine::NET_PRICE => $quoteItem->getPrice(),
                    MobileTransactionLine::PRICE => $priceIncTax ?? $quoteItem->getPriceInclTax(),
                    MobileTransactionLine::NET_AMOUNT => $quoteItem->getRowTotal(),
                    MobileTransactionLine::TAXAMOUNT => $quoteItem->getTaxAmount(),
                    MobileTransactionLine::DISCOUNT_AMOUNT => $discount,
                    MobileTransactionLine::DISCOUNT_PERCENT => $discountPercentage,
                ]);

                $mobileTransactionLines[] = $orderLine;

                if ($discountPercentage && $discount) {
                    $orderDiscountLine = $this->createInstance(MobileTransDiscountLine::class);
                    $orderDiscountLine->addData([
                        MobileTransDiscountLine::LINE_NO => $lineNumber,
                        MobileTransDiscountLine::NO => $lineNumber,
                        MobileTransDiscountLine::DISCOUNT_TYPE => 4,
                        MobileTransDiscountLine::DISCOUNT_AMOUNT => $discount,
                        MobileTransDiscountLine::DISCOUNT_PERCENT => $discountPercentage,
                    ]);
                    $mobileTransactionDiscountLines[] = $orderDiscountLine;
                }

                $lineNumber += 10000;
            }
        }

        return [$mobileTransaction, $mobileTransactionLines, $mobileTransactionDiscountLines];
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
     * Get configured store code for the current scope
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getDefaultWebStore()
    {
        if ($this->storeId == null) {
            $this->storeId = $this->lsr->getActiveWebStore();
        }

        return $this->storeId;
    }

    /**
     * Get coupon code
     *
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
     *
     * @param string $couponCode
     * @return Phrase|string|null
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     * @throws GuzzleException
     */
    public function setCouponCode(string $couponCode)
    {
        $couponCode = trim($couponCode);

        if ($couponCode == "") {
            $this->couponCode = '';
            $this->setCouponQuote("");
            $this->fetchUpdatedBasket();
            $this->itemHelper->setDiscountedPricesForItems(
                $this->checkoutSession->getQuote(),
                $this->getBasketSessionValue()
            );

            return null;
        }
        $this->couponCode = $couponCode;
        $status = $this->fetchUpdatedBasket();
        $mobileTransDiscountLines = $status ? $status->getMobiletransdiscountline() : [];
        $checkCouponAmount = $this->dataHelper->orderBalanceCheck(
            $this->checkoutSession->getQuote()->getLsGiftCardNo(),
            $this->checkoutSession->getQuote()->getLsGiftCardAmountUsed(),
            $this->checkoutSession->getQuote()->getLsPointsSpent(),
            $status,
            false
        );

        if (!is_object($status) || $checkCouponAmount) {
            $this->couponCode = '';
            $this->fetchUpdatedBasket();
            $this->setCouponQuote($this->couponCode);
            $status = __("Coupon Code is not valid");

            if ($checkCouponAmount) {
                $status = $checkCouponAmount;
            }

            return $status;
        } elseif (!empty($mobileTransDiscountLines)) {
            if (is_array($mobileTransDiscountLines)) {
                foreach ($mobileTransDiscountLines as $orderDiscountLine) {
                    if ($orderDiscountLine->getDiscounttype() == '12') {
                        $status = "success";
                        $this->itemHelper->setDiscountedPricesForItems(
                            $this->checkoutSession->getQuote(),
                            $this->getBasketSessionValue()
                        );
                        $this->setCouponQuote($this->couponCode);
                    }
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
     * Set coupon inside quote
     *
     * @param string $couponCode
     * @throws Exception
     */
    public function setCouponQuote(string $couponCode)
    {
        try {
            $cartQuote = $this->cart->getQuote();
            if (!empty($cartQuote->getId())) {
                $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                $cartQuote->setCouponCode($couponCode);
                $cartQuote->collectTotals();
            }
            $this->quoteResourceModel->save($cartQuote);
        } catch (Exception $e) {
            $this->_logger->critical($e->getMessage());
        }
    }

    /**
     * Fetch updated basket
     *
     * @return RootMobileTransaction|null
     * @throws GuzzleException
     * @throws InvalidEnumException
     * @throws NoSuchEntityException|AlreadyExistsException|LocalizedException
     */
    public function fetchUpdatedBasket()
    {
        $quote = $this->getCurrentQuote();
        $oneList = $this->basketHelper->setOneListQuote($quote, $this->get());

        return $this->update($oneList);
    }

    /**
     * Sync basket with central
     *
     * @param RootMobileTransaction $oneList
     * @param int $type
     * @return RootMobileTransaction|null
     * @throws AlreadyExistsException
     * @throws GuzzleException
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function update(RootMobileTransaction $oneList, int $type = 1)
    {
        $oneListCalculation = $this->calculate($oneList);

        if ($oneListCalculation && $type == 1) {
            $this->setOneListCalculationInCheckoutSession($oneListCalculation);
        }

        return $oneListCalculation;
    }

    /**
     * This function is overriding in hospitality module
     *
     * @param RootMobileTransaction $oneList
     * @return RootMobileTransaction|null
     * @throws InvalidEnumException|NoSuchEntityException
     * @throws Exception|GuzzleException
     */
    public function calculate(RootMobileTransaction $oneList)
    {
        if (!$this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getBasketIntegrationOnFrontend()
        )) {
            return null;
        }

        if (empty($this->getCouponCode()) && $this->calculateBasket == 1
            && empty($this->getOneListCalculationFromCheckoutSession())) {
            return null;
        }

        if ($this->getCouponCode() != "" && $this->getCouponCode() != null) {
            $mobileTransactionLines = $oneList->getMobiletransactionline();
            $lineNumber = (count($mobileTransactionLines) + 1) * 10000;
            $transactionId = $oneList->getMobiletransaction()
                ->getId();
            $storeCode = $this->getDefaultWebStore();
            $listItem = $this->createInstance(
                MobileTransactionLine::class,
                [
                    'data' => [
                        MobileTransactionLine::ID => $transactionId,
                        MobileTransactionLine::LINE_NO => $lineNumber,
                        MobileTransactionLine::STORE_ID => $storeCode,
                        MobileTransactionLine::QUANTITY => 1,
                        MobileTransactionLine::NUMBER => $this->getCouponCode(),
                        MobileTransactionLine::BARCODE => $this->getCouponCode(),
                        MobileTransactionLine::TRANS_DATE => $this->getCompatibleDateTime(),
                        MobileTransactionLine::CURRENCY_FACTOR => 1,
                        MobileTransactionLine::LINE_TYPE => 6
                    ]
                ]
            );
            $mobileTransactionLines[] = $listItem;
            $oneList->setMobiletransactionline($mobileTransactionLines);
        }
        $oneList->getMobiletransaction()
            ->setCurrencycode($this->lsr->getStoreCurrencyCode())
            ->setCurrencyfactor((float)$this->loyaltyHelper->getPointRate());
        $operation = $this->createInstance(EcomCalculateBasket::class);
        $operation->setOperationInput(
            [Entity\EcomCalculateBasket::MOBILE_TRANSACTION_XML => $oneList]
        );
        $response = $operation->execute();

        return $response && $response->getResponsecode() == "0000" ? $response->getMobiletransactionxml() : null;
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
        $this->adminOrderCardId = '';

        if (!$isGuest) {
            $customer = $this->customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($customerEmail);

            if (!empty($customer->getData('lsr_cardid'))) {
                $this->adminOrderCardId = $customer->getData('lsr_cardid');
            }
        }
        $webStore       = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $websiteId);
        $this->storeId = $webStore;
        // @codingStandardsIgnoreStart
        /** @var Entity\OneList $list */
//        $list = (new Entity\OneList())
//            ->setCardId($cardId)
//            ->setDescription('OneList Magento')
//            ->setListType(Entity\Enum\ListType::BASKET)
//            ->setItems(new Entity\ArrayOfOneListItem())
//            ->setPublishedOffers($this->_offers())
//            ->setStoreId($webStore);

        $list    = $this->get();

        return $list;
        // @codingStandardsIgnoreEnd
    }

    /**
     * Get basket wrapper
     *
     * @return RootMobileTransaction
     * @throws NoSuchEntityException
     */
    public function get(): RootMobileTransaction
    {
        return $this->fetchFromOmni();
    }

    /**
     * Fetch new basket
     *
     * @return RootMobileTransaction
     * @throws NoSuchEntityException
     */
    public function fetchFromOmni(): RootMobileTransaction
    {
        // if guest, then empty card id
        $cardId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID) ?? $this->adminOrderCardId ?? '';

        $storeCode = $this->getDefaultWebStore();

        /**
         * only those users who either does not have onelist created or
         * is guest user will come up here so for them lets create a new one.
         * for those lets create new list with no items and the existing offers and coupons
         */
        $mobileTransaction = $this->createInstance(
            MobileTransaction::class,
            [
                'data' => [
                    MobileTransaction::ID => $this->generateGuid(),
                    MobileTransaction::TRANS_DATE => $this->getCompatibleDateTime(),
                    MobileTransaction::TRANSACTION_TYPE => 2,
                    MobileTransaction::SOURCE_TYPE => 1,
                    MobileTransaction::SALES_TYPE => 'POS',
                    MobileTransaction::MEMBER_CARD_NO => $cardId,
                    MobileTransaction::STORE_ID => $storeCode
                ]
            ]
        );

        return $this->createInstance(
            RootMobileTransaction::class,
            ['data' => [
                RootMobileTransaction::MOBILE_TRANSACTION => $mobileTransaction
            ]]
        );
    }

    /**
     * Get wishlist wrapper
     *
     * @return RootMobileTransaction
     * @throws NoSuchEntityException
     */
    public function fetchCurrentCustomerWishlist(): RootMobileTransaction
    {
        return $this->fetchFromOmni();
    }

    /**
     * This function is overriding in hospitality module
     *
     * Get Correct Item Row Total for mini-cart after comparison
     *
     * @param Item $item
     * @return string
     * @throws GuzzleException
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getItemRowTotal(Item $item)
    {
        if ($item->getProductType() == Type::TYPE_BUNDLE) {
            $rowTotal = $this->getRowTotalBundleProduct($item);
        } else {
            $baseUnitOfMeasure = $item->getProduct()->getData('uom');
            list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                $item->getSku()
            );
            $rowTotal   = $item->getRowTotalInclTax();
            $basketData = $this->getOneListCalculation();
            $orderLines = $basketData ? $basketData->getMobiletransactionline() : [];

            foreach ($orderLines as $line) {
                if ($item->getProductType() == LSR::TYPE_GIFT_CARD && $line->getPrice() != $item->getCustomPrice()) {
                    continue;
                }
                if ($this->itemHelper->isValid($item, $line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                    $rowTotal = $line->getQuantity() == $item->getQty() ?
                        ($line->getNetamount() + $line->getTaxamount()) :
                        (($line->getNetamount() + $line->getTaxamount()) / $line->getQuantity()) * $item->getQty();
                    break;
                }
            }
        }

        return $rowTotal;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Get Correct Item Row Total for mini-cart after comparison
     *
     * @param $item
     * @return string
     * @throws InvalidEnumException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function getPrice($item)
    {
        if ($item->getProductType() == Type::TYPE_BUNDLE) {
            $rowTotal = $this->getRowTotalBundleProduct($item);
        } else {
            $baseUnitOfMeasure = $item->getProduct()->getData('uom');
            list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                $item->getSku()
            );
            $price      = $item->getPrice();
            $basketData = $this->getOneListCalculation();
            $orderLines = $basketData ? $basketData->getOrderLines()->getOrderLine() : [];

            foreach ($orderLines as $line) {
                if ($this->itemHelper->isValid($item, $line, $itemId, $variantId, $uom, $baseUnitOfMeasure)) {
                    $price = $line->getPrice();
                    break;
                }
            }
        }

        return $price;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Get item row discount
     *
     * @param Item $item
     * @param array $lines
     * @return float|int
     * @throws GuzzleException
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getItemRowDiscount(Item $item, array $lines = [])
    {
        $rowDiscount = $bundleProduct = 0;

        if (empty($lines)) {
            $basketData = $this->getOneListCalculation();
            $orderLines = $basketData ? $basketData->getMobiletransactionline() : [];
        } else {
            $orderLines = $lines;
        }
        if ($item->getProductType() == Type::TYPE_BUNDLE) {
            $children = !empty($lines) ? $item->getChildrenItems() : $item->getChildren();
            $bundleProduct = 1;
        } else {
            $children[] = $item;
        }

        foreach ($children as $child) {
            foreach ($orderLines as $index => $line) {
                if (is_numeric($line->getId()) ?
                    ($child->getItemId() == $line->getId() && $line->getDiscountamount() > 0) :
                    ($this->itemHelper->isSameItem($child, $line) && $line->getDiscountamount() > 0)
                ) {
                    $qty = !empty($lines) ?
                        $item->getQtyOrdered() :
                        ($bundleProduct ? $child->getData('qty') * $item->getData('qty') :
                            $child->getQty()
                        );
                    $rowDiscount += $line->getQuantity() == $qty ? $line->getDiscountamount()
                        : ($line->getDiscountAmount() / $line->getQuantity()) * $qty;
                    unset($orderLines[$index]);

                    if (!$bundleProduct) {
                        break 2;
                    } else {
                        break;
                    }
                }
            }
        }

        return $rowDiscount;
    }

    /**
     * Calculate row total of bundle adding all individual simple items
     *
     * @param $item
     * @return float
     */
    public function getRowTotalBundleProduct($item)
    {
        $rowTotal = 0.00;

        foreach ($item->getChildren() as $child) {
            $rowTotal += $child->getRowTotal();
        }

        return $rowTotal;
    }

    /**
     * Get basket calculation stored in quote
     *
     * @return RootMobileTransaction|null
     * @throws InvalidEnumException|NoSuchEntityException
     * @throws GuzzleException
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
     * @return RootMobileTransaction|null
     * @throws InvalidEnumException
     * @throws LocalizedException|GuzzleException
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
     * @param \Magento\Sales\Model\Order $order
     * @return Order
     * @throws InvalidEnumException
     * @throws LocalizedException
     */
    public function formulateCentralOrderRequestFromMagentoOrder(\Magento\Sales\Model\Order $order)
    {
        $orderEntity = $this->createInstance(RootMobileTransaction::class);

        list($mobileTransaction,
            $mobileTransactionLines,
            $mobileTransactionDiscountLines
            ) = $this->getOrderLinesQuote($order);

        $orderEntity->addData([
            RootMobileTransaction::MOBILE_TRANSACTION => $mobileTransaction,
            RootMobileTransaction::MOBILE_TRANSACTION_LINE => $mobileTransactionLines,
            RootMobileTransaction::MOBILE_TRANS_DISCOUNT_LINE => $mobileTransactionDiscountLines,
        ]);

        return $orderEntity;
    }

    /**
     * Sending request to Central for basket calculation
     *
     * @param string $cartId
     * @return RootMobileTransaction|null
     * @throws AlreadyExistsException
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function syncBasketWithCentral($cartId)
    {
        $quote      = $this->quoteRepository->getActive($cartId);
        $basketData = null;
        $oneList    = $this->get();
        // add items from the quote to the oneList and return the updated onelist
        $oneList = $this->setOneListQuote($quote, $oneList);

        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) && $oneList && $this->getCalculateBasket()) {
            $this->setCalculateBasket(false);
            $basketData = $this->updateBasketAndSaveTotals($oneList, $quote);
            $this->setCalculateBasket(true);
        }

        return $basketData;
    }

    /**
     * Updating basket from Central and storing response
     *
     * @param RootMobileTransaction $oneList
     * @param $quote
     * @return RootMobileTransaction|null
     * @throws AlreadyExistsException
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function updateBasketAndSaveTotals($oneList, $quote)
    {
        $country = $quote->getShippingAddress()->getCountryId();
        $oneList->getMobiletransaction()->setShiptocountryregioncode($country);

        $basketData = $this->update($oneList);
        $quote = $this->getCurrentQuote();
        if (is_object($basketData)) {
            $this->itemHelper->setDiscountedPricesForItems($quote, $basketData);
            $cartQuote = $this->checkoutSession->getQuote();

            if ($cartQuote->getLsGiftCardAmountUsed() > 0 ||
                $cartQuote->getLsPointsSpent() > 0) {
                $this->validateLoyaltyPointsAgainstOrderTotal($cartQuote, $basketData);
            }
        }

        if (empty($basketData) && $this->getCalculateBasket() == 1 && $this->lsr->isEnabled($quote->getStoreId())) {
            $quoteItemList = $quote->getAllVisibleItems();
            foreach ($quoteItemList as $quoteItem) {
                $quoteItem->setOriginalCustomPrice($quoteItem->getPrice());
                $quoteItem->setPriceInclTax($quoteItem->getPrice());
                $quoteItem->setBasePriceInclTax($quoteItem->getPrice());
                $quoteItem->setBasePrice($quoteItem->getPrice());
                $quoteItem->setRowTotal($quoteItem->getRowTotal());
                $quoteItem->setRowTotalInclTax($quoteItem->getRowTotal());
                $quoteItem->getProduct()->setIsSuperMode(true);
                try {
                    // @codingStandardsIgnoreLine
                    $this->getItemHelper()->itemResourceModel->save($quoteItem);
                } catch (LocalizedException $e) {
                    $this->_logger->critical(
                        "Error saving Quote Item:-" . $quoteItem->getSku() . " - " . $e->getMessage()
                    );
                }

            }
        }

        return $basketData;
    }

    /**
     * Check if loyalty points valid, if not remove loyalty points and show error msg
     *
     * @param $cartQuote
     * @param $basketData
     * @return void
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     */
    public function validateLoyaltyPointsAgainstOrderTotal($cartQuote, $basketData)
    {
        $this->dataHelper->orderBalanceCheck(
            $cartQuote->getLsGiftCardNo(),
            $cartQuote->getLsGiftCardAmountUsed(),
            $cartQuote->getLsPointsSpent(),
            $basketData
        );
        $loyaltyPoints      = $cartQuote->getLsPointsSpent();
        $orderBalance       = $this->dataHelper->getOrderBalance(
            $cartQuote->getLsGiftCardAmountUsed(),
            0,
            $this->getBasketSessionValue()
        );
        $isPointsLimitValid = $this->loyaltyHelper->isPointsLimitValid($orderBalance, $loyaltyPoints);

        if (!$isPointsLimitValid) {
            $cartQuote->setLsPointsSpent(0);
            $this->quoteRepository->save($cartQuote);
            $this->itemHelper->setGrandTotalGivenQuote($cartQuote, $basketData, 1);
        }
    }

    /**
     * Get Pickup time slot
     *
     * @param String $pickupDate
     * @param String $pickupTimeslot
     * @return string
     */
    public function getPickupTimeSlot($pickupDate, $pickupTimeslot)
    {
        $pickupDateTimeslot = '';

        if (!empty($pickupDate) && !empty($pickupTimeslot)) {
            $pickupDateFormat   = $this->lsr->getStoreConfig(LSR::PICKUP_DATE_FORMAT);
            $pickupTimeFormat   = $this->lsr->getStoreConfig(LSR::PICKUP_TIME_FORMAT);
            $pickupDateTimeslot = $pickupDate . ' ' . $pickupTimeslot;
            $pickupDateTimeslot = $this->dateTime->date(
                $pickupDateFormat . ' ' . $pickupTimeFormat,
                strtotime($pickupDateTimeslot)
            );
        } elseif (!empty($pickupDate)) {
            $pickupDateFormat   = $this->lsr->getStoreConfig(LSR::PICKUP_DATE_FORMAT);
            $pickupDateTimeslot = $this->dateTime->date(
                $pickupDateFormat,
                strtotime($pickupDate)
            );
        }

        return $pickupDateTimeslot;
    }

    /**
     * Get price include custom options price
     *
     * @param $item
     * @param $price
     * @return float|int|mixed
     */
    public function getPriceAddingCustomOptions($item, $price)
    {
        $optionIds = $item->getOptionByCode('option_ids');
        if ($optionIds) {
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                $option = $item->getProduct()->getOptionById($optionId);
                if ($option) {
                    $itemOption = $item->getOptionByCode('option_' . $option->getId());
                    if ($itemOption) {
                        $optionValue = $itemOption->getValue();
                        $values = explode(',', $optionValue); // Handle multiple selected values

                        foreach ($values as $valueId) {
                            $value = $option->getValueById($valueId);
                            if ($value) {
                                $price += $value->getPrice() * $item->getQty();
                            }
                        }
                    }
                }
            }
        }

        return $price;
    }

    /**
     * Get Basket Session Data
     * @return mixed
     * @throws NoSuchEntityException
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
     * Get current active quote
     *
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function getCurrentQuote()
    {
        $quoteId = $this->checkoutSession->getQuoteId();

        if (!$quoteId) {
            return null;
        }
        $quote = $this->quoteRepository->get($quoteId);

        return $quote;
    }

    /**
     * Set basket calculation into current quote
     *
     * @param mixed $calculation
     * @return void
     * @throws NoSuchEntityException|AlreadyExistsException
     */
    public function setOneListCalculationInCheckoutSession($calculation)
    {
        $quote = $this->getCurrentQuote();
        if ($quote) {
            $calculation = $calculation ? $this->flattenModel($calculation) : null;
            // phpcs:ignore Magento2.Security.InsecureFunction.FoundWithAlternative
            $quote->setBasketResponse($calculation ? serialize($calculation) : null);
            $this->quoteResourceModel->save($quote);
        }
    }

    /**
     * Get unique session key per store
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getOneListCalculationKey()
    {
        $storeId = $this->lsr->getCurrentStoreId();
        return LSR::SESSION_CHECKOUT_ONE_LIST_CALCULATION . '_' . $storeId;
    }

    /**
     * Get basket calculation from current quote
     *
     * @param $quote
     * @return RootMobileTransaction|null
     * @throws NoSuchEntityException
     */
    public function getOneListCalculationFromCheckoutSession($quote = null)
    {
        if (!$quote) {
            $quote = $this->getCurrentQuote();
        }

        if (!$quote) {
            return null;
        }

        $basketData = $quote->getBasketResponse();
        // phpcs:ignore Magento2.Security.InsecureFunction.FoundWithAlternative
        return ($basketData) ? $this->restoreModel(unserialize($basketData)) : $basketData;
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
     * Set store_pickup_hours in checkout session being used
     *
     * @param $hours
     */
    public function setStorePickUpHoursInCheckoutSession($hours)
    {
        $this->checkoutSession->setData(LSR::SESSION_CHECKOUT_STORE_PICKUP_HOURS, $hours);
    }

    /**
     * Get store_pickup_hours from checkout session being used
     *
     * @return mixed|null
     */
    public function getStorePickUpHoursFromCheckoutSession()
    {
        return $this->checkoutSession->getData(LSR::SESSION_CHECKOUT_STORE_PICKUP_HOURS);
    }

    /**
     * Set delivery_hours in checkout session being used
     *
     * @param $hours
     */
    public function setDeliveryHoursInCheckoutSession($hours)
    {
        $this->checkoutSession->setData(LSR::SESSION_CHECKOUT_DELIVERY_HOURS, $hours);
    }

    /**
     * Get delivery_hours from checkout session being used in case of admin
     *
     * @return mixed|null
     */
    public function getDeliveryHoursFromCheckoutSession()
    {
        return $this->checkoutSession->getData(LSR::SESSION_CHECKOUT_DELIVERY_HOURS);
    }

    /**
     * Set gift card in checkout session being used
     *
     * @param $giftCard
     */
    public function setGiftCardResponseInCheckoutSession($giftCard)
    {
        $giftCard = $giftCard ? $this->flattenModel($giftCard) : null;
        $this->checkoutSession->setData(LSR::SESSION_CHECKOUT_GIFT_CARD, $giftCard);
    }

    /**
     * Get gift card from checkout session
     *
     * @return mixed|null
     */
    public function getGiftCardResponseFromCheckoutSession()
    {
        $value = $this->checkoutSession->getData(LSR::SESSION_CHECKOUT_GIFT_CARD);
        return ($value) ? $this->restoreModel($value) : $value;
    }

    /**
     * clear store_pickup_hours from checkout session being used
     */
    public function unSetStorePickupHours()
    {
        $this->checkoutSession->unsetData(LSR::SESSION_CHECKOUT_STORE_PICKUP_HOURS);
    }

    /**
     * clear delivery_hours from checkout session being used
     */
    public function unSetDeliveryHours()
    {
        $this->checkoutSession->unsetData(LSR::SESSION_CHECKOUT_DELIVERY_HOURS);
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
        $this->checkoutSession->unsetData($this->getOneListCalculationKey());
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
        $this->unSetDeliveryHours();
        $this->unSetStorePickupHours();
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

    /**
     * Get cart repository
     *
     * @return CartRepositoryInterface
     */
    public function getCartRepositoryObject()
    {
        return $this->cartRepository;
    }
}
