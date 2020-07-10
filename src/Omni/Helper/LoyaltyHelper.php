<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Model\Cache\Type;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session\Proxy as CustomerProxy;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Quote\Model\Quote\Item;

/**
 * Class LoyaltyHelper
 * @package Ls\Omni\Helper
 */
class LoyaltyHelper extends AbstractHelper
{

    const SERVICE_TYPE = 'ecommerce';

    /**
     * @var CustomerFactory
     */
    public $customerFactory;

    /**
     * @var CustomerProxy
     */
    public $customerSession;

    /**
     * @var Filesystem
     */
    public $filesystem;

    /**
     * @var $checkoutSession
     */
    public $checkoutSession;

    /**
     * @var GroupRepositoryInterface
     */
    public $groupRepository;

    /**
     * @var CacheHelper
     */
    public $cacheHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * LoyaltyHelper constructor.
     * @param Context $context
     * @param CustomerFactory $customerFactory
     * @param CustomerProxy $customerSession
     * @param Proxy $checkoutSession
     * @param Filesystem $Filesystem
     * @param GroupRepositoryInterface $groupRepository
     * @param CacheHelper $cacheHelper
     * @param LSR $lsr
     */
    public function __construct(
        Context $context,
        CustomerFactory $customerFactory,
        CustomerProxy $customerSession,
        Proxy $checkoutSession,
        Filesystem $Filesystem,
        GroupRepositoryInterface $groupRepository,
        CacheHelper $cacheHelper,
        LSR $lsr
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->filesystem      = $Filesystem;
        $this->groupRepository = $groupRepository;
        $this->cacheHelper     = $cacheHelper;
        $this->lsr             = $lsr;
        parent::__construct(
            $context
        );
    }

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
        try {
            $response = $request->execute($entity);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * @return int
     */
    public function getMemberPoints()
    {
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
        return 0;
    }

    /*
     * Convert Point Rate into Values
     */

    /**
     * @return float|Entity\GetPointRateResponse|ResponseInterface|null
     * @throws NoSuchEntityException
     */
    public function getPointRate()
    {
        $storeId  = $this->lsr->getCurrentStoreId();
        $cacheId  = LSR::POINTRATE . $storeId;
        $response = $this->cacheHelper->getCachedContent($cacheId);
        if ($response) {
            return $response;
        }
        $response = null;
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
        $pointrate      = $this->getPointRate();
        $requiredAmount = $pointrate * $loyaltyPoints;
        if ($requiredAmount <= $grandTotal) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check user have enough points or not
     * @param $loyaltyPoints
     * @return bool
     */
    public function isPointsAreValid($loyaltyPoints)
    {
        $memberProfile = $this->getMemberInfo();
        if ($memberProfile != null) {
            $points = $memberProfile->getAccount()->getPointBalance();
            if ($points >= $loyaltyPoints) {
                return true;
            }
        } else {
            return false;
        }
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
    }

    /**
     * @param $itemId
     * @param $storeId
     * @param $cardId
     * @return bool|Entity\PublishedOffer[]|Entity\PublishedOffersGetResponse|ResponseInterface|null
     */
    public function getPublishedOffers($itemId, $storeId, $cardId)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\PublishedOffersGetByCardId();
        $entity  = new Entity\PublishedOffersGetByCardId();
        // @codingStandardsIgnoreEnd
        $cacheId  = LSR::COUPONS . $itemId . "_" . $cardId . "_" . $storeId;
        $response = $this->cacheHelper->getCachedContent($cacheId);
        if ($response) {
            $this->_logger->debug("Found coupons from cache " . $cacheId);
            return $response;
        }
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
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getAvailableCouponsForLoggedInCustomers()
    {
        $memberInfo = $this->getMemberInfo();
        if (!$memberInfo) {
            return [];
        }
        $publishedOffersObj = $memberInfo->getPublishedOffers();
        $itemsInCart        = $this->checkoutSession->getQuote()->getAllItems();
        $itemsSku           = [];
        $coupons            = [];
        /** @var Item $item */
        foreach ($itemsInCart as $item) {
            if (!empty($item->getParentItemId())) {
                $parentItem = $item->getParentItem();
                $parentSku  = $parentItem->getProduct()->getData('sku');
                if (!empty($parentSku)) {
                    $itemsSku[] = $parentSku;
                }
            } else {
                $itemsSku[] = $item->getSku();
            }
        }
        if ($publishedOffersObj) {
            $publishedOffers = $publishedOffersObj->getPublishedOffer();
            foreach ($publishedOffers as $each) {
                $getPublishedOfferLineArray = $each->getOfferLines()->getPublishedOfferLine();
                if ($each->getCode() == "Coupon" && $each->getOfferLines()) {
                    foreach ($getPublishedOfferLineArray as $publishedOfferLine) {
                        if (!empty($publishedOfferLine->getVariant())) {
                            $itemSku = $publishedOfferLine->getId() . '-' . $publishedOfferLine->getVariant();
                        } else {
                            $itemSku = $publishedOfferLine->getId();
                        }
                        if (in_array($itemSku, $itemsSku)) {
                            $coupons[] = $each;
                        }
                        if ($publishedOfferLine->getLineType() == Entity\Enum\OfferDiscountLineType::PRODUCT_GROUP
                            || $publishedOfferLine->getLineType() == Entity\Enum\OfferDiscountLineType::ITEM_CATEGORY
                            || $publishedOfferLine->getLineType() == Entity\Enum\OfferDiscountLineType::SPECIAL_GROUP
                        ) {
                            $coupons[] = $each;
                        }
                    }
                }
            }
            return $coupons;
        }
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
}
