<?php

namespace Ls\Omni\Helper;

use Carbon\Carbon;
use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountLineType;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Currency;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;

/**
 * Class LoyaltyHelper for handling loyalty points
 */
class LoyaltyHelper extends AbstractHelperOmni
{
    /**
     * To fetch all profiles
     *
     * @return Entity\ArrayOfProfile|Entity\ProfilesGetAllResponse|ResponseInterface|null
     */
    public function getAllProfiles()
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\ProfilesGetAll();
        $entity  = new Entity\ProfilesGetAll();
        // @codingStandardsIgnoreEnd
        try {
            $response = $request->execute($entity);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * To get all customer offers
     *
     * @return Entity\ArrayOfPublishedOffer|Entity\PublishedOffersGetByCardIdResponse|ResponseInterface|null
     */
    public function getOffers()
    {
        $response = null;
        $customer = $this->customerSession->getCustomer();
        // @codingStandardsIgnoreLine
        $request = new Operation\PublishedOffersGetByCardId();
        // @codingStandardsIgnoreLine
        $entity = new Entity\PublishedOffersGetByCardId();
        $entity->setCardId($customer->getData('lsr_cardid'));
        $entity->setItemId('');
        try {
            $response = $request->execute($entity);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * Get image by id
     *
     * @param mixed $image_id
     * @param mixed $image_size
     * @return array|bool
     * @throws NoSuchEntityException
     */
    public function getImageById($image_id = null, $image_size = null)
    {
        if ($image_id == null || $image_size == null) {
            return [];
        }

        $cacheId = $this->getImageCacheId($image_id);

        $response = $this->cacheHelper->getCachedContent($cacheId);

        if ($response) {
            $this->_logger->debug("Found image from cache " . $cacheId);
            return $response;
        }
        // @codingStandardsIgnoreStart
        $request = new Operation\ImageGetById();
        $entity  = new Entity\ImageGetById();
        // @codingStandardsIgnoreEnd
        $entity->setId($image_id)
            ->setImageSize($image_size);

        try {
            $response = $request->execute($entity);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if (!empty($response) && !empty($response->getResult())) {
            $this->cacheHelper->persistContentInCache(
                $cacheId,
                [
                    "image"        => $response->getResult()->getImage(),
                    "format"       => $response->getResult()->getFormat(),
                    "location"     => $response->getResult()->getLocation(),
                    "locationType" => $response->getResult()->getLocationType()
                ],
                [Type::CACHE_TAG],
                604800
            );
            return [
                "image"        => $response->getResult()->getImage(),
                "format"       => $response->getResult()->getFormat(),
                "location"     => $response->getResult()->getLocation(),
                "locationType" => $response->getResult()->getLocationType()
            ];
        }
        return [];
    }

    /**
     * Get image cache id
     *
     * @param string $imageId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getImageCacheId($imageId)
    {
        $storeId = $this->lsr->getCurrentStoreId();
        $cacheId = LSR::IMAGE_CACHE . $imageId;

        if (!$this->imageCacheIndependenOfStoreId()) {
            $cacheId .= "_" . $storeId;
        }

        return $cacheId;
    }

    /**
     * To convert points to values
     *
     * @return float|int
     * @throws NoSuchEntityException
     */
    public function convertPointsIntoValues()
    {
        $points        = $pointRate = $value = 0;
        $memberProfile = $this->getMemberInfo();
        $pointRate     = $this->getPointRate();
        if ($memberProfile != null && $pointRate != null) {
            $points = $memberProfile->getAccount()->getPointBalance();
            $value  = $points * $pointRate;
            return $value;
        } else {
            return 0;
        }
    }

    /**
     * Get loyalty points available to customer
     *
     * @return int|Entity\CardGetPointBalanceResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getLoyaltyPointsAvailableToCustomer()
    {
        $cardId = $this->contactHelper->getCardIdFromCustomerSession();
        if (!$cardId) { //fetch card id from customer object if session value not available
            $customerId = $this->customerSession->getCustomerId();
            $customer   = $this->customerFactory->create()->load($customerId);
            $cardId     = $customer->getLsrCardid();
        }
        $points   = $this->basketHelper->getMemberPointsFromCheckoutSession();
        $response = null;

        if ($cardId && $points == null && $this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            // @codingStandardsIgnoreStart
            $request = new Operation\CardGetPointBalance();
            $entity  = new Entity\CardGetPointBalance();
            // @codingStandardsIgnoreEnd
            $entity->setCardId($cardId);
            try {
                $response = $request->execute($entity);
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            $points = $response ? $response->getResult() : 0;

            $this->basketHelper->setMemberPointsInCheckoutSession($points);
        }

        return $points ?? 0;
    }

    /**
     * To get contact by card id
     *
     * @return Entity\ContactGetByCardIdResponse|Entity\MemberContact|ResponseInterface|null
     */
    public function getMemberInfo()
    {
        $response = null;
        $customer = $this->customerSession->getCustomer();
        $cardId   = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        // if not set in session then get it from customer database.
        if (!$cardId) {
            $cardId = $customer->getData('lsr_cardid');
        }
        // @codingStandardsIgnoreLine
        $request = new Operation\ContactGetByCardId();
        // @codingStandardsIgnoreLine
        $entity = new Entity\ContactGetByCardId();
        $entity->setCardId($cardId);
        $entity->setNumberOfTransReturned(1);
        try {
            $response = $request->execute($entity);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * To get point balance expiry sum
     *
     * @return false|float|int
     * @throws NoSuchEntityException
     */
    public function getPointBalanceExpirySum()
    {
        if (version_compare($this->lsr->getOmniVersion(), '2023.06', '<')) {
            return false;
        }

        $totalEarnedPoints = 0;
        $totalRedemption   = 0;
        $totalExpiryPoints = 0;
        $result            = $this->getCardGetPointEntries();
        $expiryInterval    = $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_POINTS_EXPIRY_NOTIFICATION_INTERVAL,
            $this->lsr->getCurrentStoreId()
        );

        if ($result) {
            $startDateTs = Carbon::now();
            $endDateTs   = Carbon::now()->addDays((int)$expiryInterval);

            foreach ($result as $res) {
                $entryType      = $res->getEntryType();
                $expirationDate = Carbon::parse($res->getExpirationDate());
                if ($entryType == "Sales" && $expirationDate->between($startDateTs, $endDateTs, true)) {
                    $totalEarnedPoints += $res->getPoints();
                } elseif ($entryType == "Redemption" && $expirationDate->between($startDateTs, $endDateTs, true)) {
                    $totalRedemption += $res->getPoints();
                }
            }

            //Convert to negative redemption points to positive for ease of calculation
            $totalRedemption = abs($totalRedemption);
            if ($totalEarnedPoints >= $totalRedemption) {
                $totalExpiryPoints = $totalEarnedPoints - $totalRedemption;
            }

        }

        return $totalExpiryPoints;
    }

    /**
     * To fetch card point entry details
     *
     * @return Entity\ArrayOfPointEntry|Entity\CardGetPointEntriesResponse|ResponseInterface|null
     */
    public function getCardGetPointEntries()
    {
        $response = null;
        $customer = $this->customerSession->getCustomer();
        $cardId   = $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
        // if not set in session then get it from customer database.
        if (!$cardId) {
            $cardId = $customer->getData('lsr_cardid');
        }
        // @codingStandardsIgnoreLine
        $request = new Operation\CardGetPointEntries();
        // @codingStandardsIgnoreLine
        $entity = new Entity\CardGetPointEntries();
        $entity->setCardId($cardId);
        try {
            $response = $request->execute($entity);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * To get memeber points
     *
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getMemberPoints()
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $points = $this->checkoutSession->getMemberPoints();
            if (isset($points)) {
                return $points;
            }
            if ($this->customerSession->isLoggedIn()) {
                $memberProfile = $this->getMemberInfo();
                if ($memberProfile != null) {
                    $points = $memberProfile->getAccount()->getPointBalance();
                    $this->checkoutSession->setMemberPoints($points);
                    return $points;
                }
            }
        }
        return 0;
    }

    /**
     * Convert Point Rate into Values
     *
     * @return float|Entity\GetPointRateResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getPointRate($storeId = null)
    {
        if (!$storeId) {
            $storeId = $this->lsr->getCurrentStoreId();
        }

        $response = null;

        if ($this->lsr->isLSR($storeId) && $this->isEnabledLoyaltyPoints()) {
            $cacheId  = LSR::POINTRATE . $storeId;
            $response = $this->cacheHelper->getCachedContent($cacheId);

            if ($response !== false) {
                return $this->formatValue($response);
            }
            // @codingStandardsIgnoreStart
            $request = new Operation\GetPointRate();
            $entity  = new Entity\GetPointRate();
            // @codingStandardsIgnoreEnd

            $currency = $this->lsr->getStoreCurrencyCode();
            $entity->setCurrency($currency);

            try {
                $response = $request->execute($entity);
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            if (!empty($response)) {
                $this->cacheHelper->persistContentInCache(
                    $cacheId,
                    $response->getResult(),
                    [Type::CACHE_TAG],
                    86400
                );

                return $this->formatValue($response->getResult());
            }
        }
        return $response;
    }

    /**
     * To get image size
     *
     * @param mixed $size
     * @return Entity\ImageSize
     */
    public function getImageSize($size = null)
    {
        // @codingStandardsIgnoreLine
        $imagesize = new Entity\ImageSize();
        $imagesize->setHeight($size['height'])
            ->setWidth($size['width']);
        return $imagesize;
    }

    /**
     * Get media path to store
     *
     * @return string
     */
    public function getMediaPathtoStore()
    {
        return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
    }

    /**
     * Check the discount is not crossing the grand total amount
     *
     * @param float $grandTotal
     * @param int $loyaltyPoints
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPointsLimitValid($grandTotal, $loyaltyPoints)
    {
        $pointRate      = $this->getPointRate();
        $requiredAmount = $pointRate * $loyaltyPoints;

        return $requiredAmount <= $grandTotal;
    }

    /**
     * Check user have enough points or not
     *
     * @param int $loyaltyPoints
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPointsAreValid($loyaltyPoints)
    {
        $points = $this->getLoyaltyPointsAvailableToCustomer();

        return $points >= $loyaltyPoints;
    }

    /**
     * Fetch discounts
     *
     * @param string $itemId
     * @param int $webStore
     * @return bool|Entity\DiscountsGetResponse|Entity\ProactiveDiscount[]|ResponseInterface|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProactiveDiscounts($itemId, $webStore)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $response = null;
            // @codingStandardsIgnoreStart
            $request = new Operation\DiscountsGet();
            $entity  = new Entity\DiscountsGet();
            $string  = new Entity\ArrayOfstring();
            // @codingStandardsIgnoreEnd
            $storeId         = $this->lsr->getCurrentStoreId();
            $customerGroupId = $this->customerSession->getCustomerGroupId();
            $cacheItemId     = $itemId;

            if (is_array($itemId)) {
                $cacheItemId = implode('_', $itemId);
            }
            $cacheId  = LSR::PROACTIVE_DISCOUNTS . $cacheItemId . "_" . $customerGroupId . "_" . $storeId;
            $response = $this->cacheHelper->getCachedContent($cacheId);
            if ($response) {
                $this->_logger->debug("Found proactive discounts from cache " . $cacheId);
                return $response;
            }
            $group = $this->groupRepository->getById($customerGroupId)->getCode();
            $string->setString(is_array($itemId) ? $itemId : [$itemId]);
            $entity->setStoreId($webStore)->setItemIds($string)->setLoyaltySchemeCode($group);
            try {
                $response = $request->execute($entity);
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            if (!empty($response) &&
                !empty($response->getDiscountsGetResult())) {
                $this->cacheHelper->persistContentInCache(
                    $cacheId,
                    $response->getDiscountsGetResult()->getProactiveDiscount(),
                    [Type::CACHE_TAG],
                    7200
                );
                return $response->getDiscountsGetResult()->getProactiveDiscount();
            } else {
                return $response;
            }
        } else {
            return null;
        }
    }

    /**
     * Get published offers for given card_id, store_id, item_id
     *
     * @param int $cardId
     * @param int $storeId
     * @param string $itemId
     * @return bool|Entity\PublishedOffer[]|Entity\PublishedOffersGetByCardIdResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getPublishedOffers($cardId, $storeId, $itemId = null)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $cacheId  = LSR::COUPONS;
            $cacheId  = $itemId ? $cacheId . $itemId . '_' : $cacheId;
            $cacheId  .= $cardId . "_" . $storeId;
            $response = $this->cacheHelper->getCachedContent($cacheId);

            if ($response) {
                $this->_logger->debug("Found coupons from cache " . $cacheId);

                return $response;
            }

            // @codingStandardsIgnoreStart
            $request = new Operation\PublishedOffersGetByCardId();
            $entity  = new Entity\PublishedOffersGetByCardId();
            // @codingStandardsIgnoreEnd
            $entity->setCardId($cardId);
            $entity->setItemId($itemId);
            try {
                $response = $request->execute($entity);
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }

            if (!empty($response) &&
                !empty($response->getPublishedOffersGetByCardIdResult())) {
                $this->cacheHelper->persistContentInCache(
                    $cacheId,
                    $response->getPublishedOffersGetByCardIdResult()->getPublishedOffer(),
                    [Type::CACHE_TAG],
                    7200
                );
                return $response->getPublishedOffersGetByCardIdResult()->getPublishedOffer();
            } else {
                return $response;
            }
        } else {
            return null;
        }
    }

    /**
     * Get available coupons for logged in customers
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function getAvailableCouponsForLoggedInCustomers()
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $storeId = $this->lsr->getActiveWebStore();
            $cardId  = $this->contactHelper->getCardIdFromCustomerSession();
            if (!$cardId) { //fetch card id from customer object if session value not available
                $customerId = $this->customerSession->getCustomerId();
                $customer   = $this->customerFactory->create()->load($customerId);
                $cardId     = $customer->getLsrCardid();
            }
            $publishedOffersObj = $this->getPublishedOffers($cardId, $storeId);
            $itemsInCart        = $this->checkoutSession->getQuote()->getAllVisibleItems();
            $coupons            = $itemIdentifiers = [];
            /** @var Item $item */
            foreach ($itemsInCart as $item) {
                if ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                    $children = $item->getChildren();
                } else {
                    $children[] = $item;
                }

                foreach ($children as $child) {
                    list($itemId, $variantId, $uom, , , $baseUom) = $this->itemHelper->getComparisonValues(
                        $child->getSku(),
                        $child->getProductId()
                    );
                    $itemIdentifiers[] = [
                        'itemId'    => $itemId,
                        'variantId' => $variantId,
                        'uom'       => $uom,
                        'baseUom'   => $baseUom
                    ];
                }
            }

            if ($publishedOffersObj) {
                foreach ($publishedOffersObj as $each) {
                    $getPublishedOfferLineArray = $each->getOfferLines()->getPublishedOfferLine();

                    if ($each->getCode() == "Coupon" && $each->getOfferLines()) {
                        foreach ($getPublishedOfferLineArray as $publishedOfferLine) {
                            if ($this->itemExistsInCart($publishedOfferLine, $itemIdentifiers)) {
                                $coupons[] = $each;
                            }

                            if ($publishedOfferLine->getLineType() == Entity\Enum\OfferDiscountLineType::PRODUCT_GROUP
                                || $publishedOfferLine->getLineType() == OfferDiscountLineType::ITEM_CATEGORY
                                || $publishedOfferLine->getLineType() == OfferDiscountLineType::SPECIAL_GROUP
                            ) {
                                $coupons[] = $each;
                            }
                        }
                    }
                }
                return $coupons;
            }
        }
        return [];
    }

    /**
     * Item exists in cart
     *
     * @param mixed $publishedOfferLine
     * @param array $itemIdentifiers
     * @return bool
     */
    public function itemExistsInCart($publishedOfferLine, $itemIdentifiers)
    {
        $flag = false;

        foreach ($itemIdentifiers as $identifier) {
            if ($publishedOfferLine->getId() == $identifier['itemId'] &&
                (
                    $publishedOfferLine->getVariant() == $identifier['variantId'] ||
                    $publishedOfferLine->getVariant() == ''
                ) &&
                (
                    $publishedOfferLine->getUnitOfMeasure() == $identifier['uom'] ||
                    ($publishedOfferLine->getUnitOfMeasure() == '' && $identifier['uom'] == $identifier['baseUom'])
                )
            ) {
                $flag = true;
                break;
            }
        }

        return $flag;
    }

    /**
     * To check if loyalty points enabled
     *
     * @param string $area
     * @return string
     * @throws NoSuchEntityException
     */
    public function isLoyaltyPointsEnabled($area)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($area == "cart") {
                return $this->lsr->getStoreConfig(
                        LSR::LS_ENABLE_LOYALTYPOINTS_ELEMENTS,
                        $this->lsr->getCurrentStoreId()
                    ) && $this->lsr->getStoreConfig(
                        LSR::LS_LOYALTYPOINTS_SHOW_ON_CART,
                        $this->lsr->getCurrentStoreId()
                    );
            }
            return $this->lsr->getStoreConfig(
                    LSR::LS_ENABLE_LOYALTYPOINTS_ELEMENTS,
                    $this->lsr->getCurrentStoreId()
                ) && $this->lsr->getStoreConfig(
                    LSR::LS_LOYALTYPOINTS_SHOW_ON_CHECKOUT,
                    $this->lsr->getCurrentStoreId()
                );
        } else {
            return false;
        }
    }

    /**
     * To check if loyalty elements enabled
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledLoyaltyElements()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_ENABLE_LOYALTY_ELEMENTS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if loyalty points enabled
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledLoyaltyPoints()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_ENABLE_LOYALTYPOINTS_ELEMENTS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show loyalty offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowLoyaltyOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_LOYALTY_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show point offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowPointOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_POINT_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show member offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowMemberOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_MEMBER_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show general offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowGeneralOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_GENERAL_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * To check if enabled to show coupon offers
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function isEnabledShowCouponOffers()
    {
        return $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_SHOW_COUPON_OFFERS,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * Image cache is independent of store
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function imageCacheIndependenOfStoreId()
    {
        return $this->lsr->getStoreConfig(
            LSR::IMAGE_CACHE_INDEPENDENT_OF_STORE_ID,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * Format value to two decimal places
     *
     * @param float $value
     * @return string
     */
    public function formatValue($value)
    {
        if ($value) {
            $formattedValue = $this->currencyHelper->format(
                $value,
                ['display' => Currency::NO_SYMBOL],
                false
            );
            return str_replace(',', '.', $formattedValue);
        }

        return '';
    }
}
