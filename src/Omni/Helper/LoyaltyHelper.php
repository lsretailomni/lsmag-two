<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Model\Cache\Type;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountLineType;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item;

/**
 * Class LoyaltyHelper for handling loyalty points
 */
class LoyaltyHelper extends AbstractHelperOmni
{
    /**
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
     * @return Entity\ArrayOfPublishedOffer|Entity\PublishedOffersGetByCardIdResponse|ResponseInterface|null
     */
    public function getOffers()
    {
        $response = null;
        $customer = $this->customerSession->getCustomer();
        // @codingStandardsIgnoreLine
        $request = new Operation\PublishedOffersGetByCardId();
        $request->setToken($customer->getData('lsr_token'));
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
     * @param null $image_id
     * @param null $image_size
     * @return array|bool
     * @throws NoSuchEntityException
     */
    public function getImageById($image_id = null, $image_size = null)
    {
        if ($image_id == null || $image_size == null) {
            return [];
        }
        $storeId  = $this->lsr->getCurrentStoreId();
        $cacheId  = LSR::IMAGE_CACHE . $image_id . "_" . $storeId;
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
                ["image" => $response->getResult()->getImage(), "format" => $response->getResult()->getFormat()],
                [Type::CACHE_TAG],
                604800
            );
            return ["image" => $response->getResult()->getImage(), "format" => $response->getResult()->getFormat()];
        }
        return [];
    }

    /**
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
        $points = $this->basketHelper->getMemberPointsFromCheckoutSession();
        $response = null;

        if ($points == null && $this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            // @codingStandardsIgnoreStart
            $request = new Operation\CardGetPointBalance();
            $entity = new Entity\CardGetPointBalance();
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
        $request->setToken($customer->getData('lsr_token'));
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
     * @return float|Entity\GetPointRateResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getPointRate()
    {
        $storeId  = $this->lsr->getCurrentStoreId();
        $response = null;
        if ($this->lsr->isLSR($storeId)) {
            $cacheId  = LSR::POINTRATE . $storeId;
            $response = $this->cacheHelper->getCachedContent($cacheId);
            if ($response) {
                return $response;
            }
            // @codingStandardsIgnoreStart
            $request = new Operation\GetPointRate();
            $entity  = new Entity\GetPointRate();
            // @codingStandardsIgnoreEnd
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
                return $response->getResult();
            }
        }
        return $response;
    }

    /**
     * @param null $size
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
     * @return string
     */
    public function getMediaPathtoStore()
    {
        return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
    }

    /**
     * Check the discount is not crossing the grand total amount
     * @param $grandTotal
     * @param $loyaltyPoints
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
     * @param $loyaltyPoints
     * @return bool
     */
    public function isPointsAreValid($loyaltyPoints)
    {
        $points = $this->getLoyaltyPointsAvailableToCustomer();

        return $points >= $loyaltyPoints;
    }

    /**
     * @param $itemId
     * @param $webStore
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
            $cacheId         = LSR::PROACTIVE_DISCOUNTS . $itemId . "_" . $customerGroupId . "_" . $storeId;
            $response        = $this->cacheHelper->getCachedContent($cacheId);
            if ($response) {
                $this->_logger->debug("Found proactive discounts from cache " . $cacheId);
                return $response;
            }
            $group = $this->groupRepository->getById($customerGroupId)->getCode();
            $string->setString([$itemId]);
            $entity->setStoreId($webStore)->setItemiIds($string)->setLoyaltySchemeCode($group);
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
     * @param $cardId
     * @param $storeId
     * @param $itemId
     * @return bool|Entity\PublishedOffer[]|Entity\PublishedOffersGetByCardIdResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getPublishedOffers($cardId, $storeId, $itemId = null)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $cacheId = LSR::COUPONS;
            $cacheId = $itemId ? $cacheId . $itemId . '_' : $cacheId;
            $cacheId .= $cardId . "_" . $storeId;
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
            $storeId            = $this->lsr->getActiveWebStore();
            $cardId             = $this->contactHelper->getCardIdFromCustomerSession();
            $publishedOffersObj = $this->getPublishedOffers($cardId, $storeId);
            $itemsInCart        = $this->checkoutSession->getQuote()->getAllItems();
            $itemsSku           = [];
            $coupons            = [];
            /** @var Item $item */
            foreach ($itemsInCart as $item) {
                if (!empty($item->getParentItemId())) {
                    $parentItem = $item->getParentItem();
                    $parentSku  = $parentItem->getProduct()->getData('sku');
                    if (!empty($parentSku)) {
                        if (!empty($parentItem)) {
                            $itemsSku[] = $parentSku;
                            if (!empty($item->getProduct()->getData('uom'))) {
                                $itemsSku[] = $parentSku . '-' . $item->getProduct()->getData('uom');
                            }
                        }
                    }
                } else {
                    $itemsSku[] = $item->getSku();
                    if (!empty($item->getProduct()->getData('uom'))) {
                        $itemsSku[] = $item->getSku() . '-' . $item->getProduct()->getData('uom');
                    }
                }
            }

            if ($publishedOffersObj) {
                foreach ($publishedOffersObj as $each) {
                    $getPublishedOfferLineArray = $each->getOfferLines()->getPublishedOfferLine();
                    if ($each->getCode() == "Coupon" && $each->getOfferLines()) {
                        foreach ($getPublishedOfferLineArray as $publishedOfferLine) {
                            if (!empty($publishedOfferLine->getVariant())) {
                                $itemSku = $publishedOfferLine->getId() . '-' . $publishedOfferLine->getVariant();
                            } else {
                                $itemSku = $publishedOfferLine->getId();
                            }
                            if (!empty($publishedOfferLine->getUnitOfMeasure())) {
                                $itemSku = $itemSku . '-' . $publishedOfferLine->getUnitOfMeasure();
                            }
                            if (in_array($itemSku, $itemsSku)) {
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
     * @param $area
     * @return string
     * @throws NoSuchEntityException
     */
    public function isLoyaltyPointsEnabled($area)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($area == "cart") {
                return $this->lsr->getStoreConfig(
                    LSR::LS_LOYALTYPOINTS_SHOW_ON_CART,
                    $this->lsr->getCurrentStoreId()
                );
            }
            return $this->lsr->getStoreConfig(
                LSR::LS_LOYALTYPOINTS_SHOW_ON_CHECKOUT,
                $this->lsr->getCurrentStoreId()
            );
        } else {
            return false;
        }
    }

    /**
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
     * Format value to two decimal places
     * @param $value
     * @return float|string
     */
    public function formatValue($value)
    {
        return $this->currencyHelper->format($value, ['display' => \Zend_Currency::NO_SYMBOL], false);
    }
}
