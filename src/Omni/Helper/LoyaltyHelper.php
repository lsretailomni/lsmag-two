<?php

namespace Ls\Omni\Helper;

use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\CacheHelper;
use \Ls\Omni\Model\Cache\Type;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class LoyaltyHelper
 * @package Ls\Omni\Helper
 */
class LoyaltyHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    const SERVICE_TYPE = 'ecommerce';

    /** @var \Magento\Framework\Api\FilterBuilder */
    public $filterBuilder;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    public $storeManager;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    public $customerRepository;

    /** @var \Magento\Customer\Model\CustomerFactory */
    public $customerFactory;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /** @var null */
    public $ns = null;

    /** @var \Magento\Framework\Filesystem */
    public $filesystem;

    /**
     * @var $checkoutSession
     */
    public $checkoutSession;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    public $groupRepository;

    /**
     * @var \Ls\Omni\Helper\CacheHelper
     */
    public $cacheHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * LoyaltyHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Framework\Filesystem $Filesystem
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Ls\Omni\Helper\CacheHelper $cacheHelper
     * @param LSR $lsr
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Framework\Filesystem $Filesystem,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        CacheHelper $cacheHelper,
        LSR $lsr
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->filesystem = $Filesystem;
        $this->groupRepository = $groupRepository;
        $this->cacheHelper = $cacheHelper;
        $this->lsr = $lsr;
        parent::__construct(
            $context
        );
    }

    /**
     * @return Entity\ArrayOfProfile|Entity\ProfilesGetAllResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getAllProfiles()
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\ProfilesGetAll();
        $entity = new Entity\ProfilesGetAll();
        // @codingStandardsIgnoreEnd
        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * @return Entity\ArrayOfPublishedOffer|Entity\PublishedOffersGetByCardIdResponse|\Ls\Omni\Client\ResponseInterface|null
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

        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getResult() : $response;
    }

    /**
     * @param null $image_id
     * @param null $image_size
     * @return array|bool|Entity\ImageGetByIdResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getImageById($image_id = null, $image_size = null)
    {
        $response = null;
        if ($image_id == null || $image_size == null) {
            return $response;
        }
        $cacheId = LSR::IMAGE_CACHE.$image_id;
        $response = $this->cacheHelper->getCachedContent($cacheId);
        if ($response) {
            $this->_logger->debug("Found image from cache ".$cacheId);
            return $response;
        }
        // @codingStandardsIgnoreStart
        $request = new Operation\ImageGetById();
        $entity = new Entity\ImageGetById();
        // @codingStandardsIgnoreEnd
        $entity->setId($image_id)
            ->setImageSize($image_size);

        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if ($response->getResult()->getImage()) {
            $this->cacheHelper->persistContentInCache(
                $cacheId,
                ["image" => $response->getResult()->getImage(), "format" => $response->getResult()->getFormat()],
                [Type::CACHE_TAG],
                172800
            );
        }
        return $response->getResult()->getImage() ?
            ["image"=>$response->getResult()->getImage(), "format"=> $response->getResult()->getFormat()]: $response;
    }

    /**
     * @return float|int
     */
    public function convertPointsIntoValues()
    {
        $points = $pointrate = $value = 0;
        /* \Ls\Omni\Client\Ecommerce\Entity\MemberContact $memberProfile */
        $memberProfile = $this->getMemberInfo();
        $pointrate = $this->getPointRate();

        // check if we have something in there.
        if ($memberProfile != null and $pointrate != null) {
            $points = $memberProfile->getAccount()->getPointBalance();
            $value = $points * $pointrate;
            return $value;
        } else {
            // if no then just return 0 value
            return 0;
        }
    }

    /**
     * @return Entity\ContactGetByIdResponse|Entity\MemberContact|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getMemberInfo()
    {

        $response = null;
        $customer = $this->customerSession->getCustomer();
        $lsrId = $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID);
        // if not set in seesion then get it from customer database.
        if (!$lsrId) {
            $lsrId = $customer->getData('lsr_id');
        }
        // @codingStandardsIgnoreLine
        $request = new Operation\ContactGetById();
        $request->setToken($customer->getData('lsr_token'));
        // @codingStandardsIgnoreLine
        $entity = new Entity\ContactGetById();
        $entity->setContactId($lsrId);

        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
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
     * @return float|Entity\GetPointRateResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getPointRate()
    {
        $storeId = $this->lsr->getDefaultWebStore();
        $cacheId = LSR::POINTRATE.$storeId;
        $response = $this->cacheHelper->getCachedContent($cacheId);
        if ($response) {
            $this->_logger->debug("Found point rate from cache ".$cacheId);
            return $response;
        }
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\GetPointRate();
        $entity = new Entity\GetPointRate();
        // @codingStandardsIgnoreEnd
        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if ($response->getResult()) {
            $this->cacheHelper->persistContentInCache(
                $cacheId,
                $response->getResult(),
                [Type::CACHE_TAG],
                7200
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
     */
    public function isPointsLimitValid($grandTotal, $loyaltyPoints)
    {
        $pointrate = $this->getPointRate();
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
        /* \Ls\Omni\Client\Ecommerce\Entity\MemberContact $memberProfile */
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
     * @param $storeId
     * @return bool|Entity\DiscountsGetResponse|Entity\ProactiveDiscount[]|\Ls\Omni\Client\ResponseInterface|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProactiveDiscounts($itemId, $storeId)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\DiscountsGet();
        $entity = new Entity\DiscountsGet();
        $string = new Entity\ArrayOfstring();
        // @codingStandardsIgnoreEnd
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        $cacheId = LSR::PROACTIVE_DISCOUNTS.$itemId."_".$customerGroupId."_".$storeId;
        $response = $this->cacheHelper->getCachedContent($cacheId);
        if ($response) {
            $this->_logger->debug("Found proactive discounts from cache ".$cacheId);
            return $response;
        }
        $group = $this->groupRepository->getById($customerGroupId)->getCode();
        $string->setString([$itemId]);
        $entity->setStoreId($storeId)->setItemiIds($string)->setLoyaltySchemeCode($group);
        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if ($response->getDiscountsGetResult()->getProactiveDiscount()) {
            $this->cacheHelper->persistContentInCache(
                $cacheId,
                $response->getDiscountsGetResult()->getProactiveDiscount(),
                [Type::CACHE_TAG],
                7200
            );
            return $response->getDiscountsGetResult()->getProactiveDiscount();
        }
        return $response;
    }

    /**
     * @param $itemId
     * @param $storeId
     * @param $cardId
     * @return bool|Entity\PublishedOffer[]|Entity\PublishedOffersGetResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function getPublishedOffers($itemId, $storeId, $cardId)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\PublishedOffersGet();
        $entity = new Entity\PublishedOffersGet();
        // @codingStandardsIgnoreEnd
        $cacheId = LSR::COUPONS.$itemId."_".$cardId."_".$storeId;
        $response = $this->cacheHelper->getCachedContent($cacheId);
        if ($response) {
            $this->_logger->debug("Found coupons from cache ".$cacheId);
            return $response;
        }
        $entity->setStoreId($storeId)->setItemId($itemId)->setStoreId($storeId)->setCardId($cardId);
        try {
            $response = $request->execute($entity);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if ($response->getPublishedOffersGetResult()->getPublishedOffer()) {
            $this->cacheHelper->persistContentInCache(
                $cacheId,
                $response->getPublishedOffersGetResult()->getPublishedOffer(),
                [Type::CACHE_TAG],
                7200
            );
            return $response->getPublishedOffersGetResult()->getPublishedOffer();
        }
        return $response;
    }

    /**
     * @return array
     */
    public function getAvailableCouponsForLoggedInCustomers()
    {
        $memberInfo = $this->getMemberInfo();
        $publishedOffersObj = $memberInfo->getPublishedOffers();
        $itemsInCart = $this->checkoutSession->getQuote()->getAllItems();
        $coupons = [];
        foreach ($itemsInCart as &$item) {
            $item = $item->getSku();
        }
        if ($publishedOffersObj) {
            $publishedOffers = $publishedOffersObj->getPublishedOffer();
            foreach ($publishedOffers as $each) {
                if ($each->getCode() == "Coupon" && $each->getOfferLines()) {
                    $itemId = $each->getOfferLines()->getPublishedOfferLine()->getId();
                    if (in_array($itemId, $itemsInCart)) {
                        $coupons[] = $each;
                    }
                }
            }
        }
        return $coupons;
    }
}
