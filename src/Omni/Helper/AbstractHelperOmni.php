<?php

namespace Ls\Omni\Helper;

use \Ls\Core\Model\LSR;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;

/**
 * Abstract Helper for merging common data members and member functions
 */
class AbstractHelperOmni extends AbstractHelper
{
    /**
     * @var CustomerFactory
     */
    public $customerFactory;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var Filesystem
     */
    public $filesystem;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var GroupRepositoryInterface
     */
    public $groupRepository;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Currency
     */
    public $currencyHelper;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var CacheHelper
     */
    public $cacheHelper;

    /**
     * @var ContactHelper
     */
    public $contactHelper;

    /**
     * @var Data
     */
    public $dataHelper;

    /**
     * @var GiftCardHelper
     */
    public $giftCardHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var LSRecommend
     */
    public $lsRecommendHelper;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var SessionHelper
     */
    public $sessionHelper;

    /**
     * @var StockHelper
     */
    public $stockHelper;

    /**
     * @var StoreHelper
     */
    public $storeHelper;

    /**
     * @param Context $context
     * @param BasketHelper $basketHelper
     * @param CacheHelper $cacheHelper
     * @param ContactHelper $contactHelper
     * @param Data $dataHelper
     * @param GiftCardHelper $giftCardHelper
     * @param ItemHelper $itemHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSRecommend $lsRecommendHelper
     * @param OrderHelper $orderHelper
     * @param SessionHelper $sessionHelper
     * @param StockHelper $stockHelper
     * @param StoreHelper $storeHelper
     * @param CustomerFactory $customerFactory
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param Filesystem $Filesystem
     * @param GroupRepositoryInterface $groupRepository
     * @param LSR $lsr
     * @param Currency $currencyHelper
     */
    public function __construct(
        Context $context,
        BasketHelper $basketHelper,
        CacheHelper $cacheHelper,
        ContactHelper $contactHelper,
        Data $dataHelper,
        GiftCardHelper $giftCardHelper,
        ItemHelper $itemHelper,
        LoyaltyHelper $loyaltyHelper,
        LSRecommend $lsRecommendHelper,
        OrderHelper $orderHelper,
        SessionHelper $sessionHelper,
        StockHelper $stockHelper,
        StoreHelper $storeHelper,
        CustomerFactory $customerFactory,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        Filesystem $Filesystem,
        GroupRepositoryInterface $groupRepository,
        LSR $lsr,
        Currency $currencyHelper
    ) {
        parent::__construct($context);
        $this->basketHelper      = $basketHelper;
        $this->cacheHelper       = $cacheHelper;
        $this->contactHelper     = $contactHelper;
        $this->dataHelper        = $dataHelper;
        $this->giftCardHelper    = $giftCardHelper;
        $this->itemHelper        = $itemHelper;
        $this->loyaltyHelper     = $loyaltyHelper;
        $this->lsRecommendHelper = $lsRecommendHelper;
        $this->orderHelper       = $orderHelper;
        $this->sessionHelper     = $sessionHelper;
        $this->stockHelper       = $stockHelper;
        $this->storeHelper       = $storeHelper;
        $this->customerFactory   = $customerFactory;
        $this->customerSession   = $customerSession;
        $this->checkoutSession   = $checkoutSession;
        $this->filesystem        = $Filesystem;
        $this->groupRepository   = $groupRepository;
        $this->lsr               = $lsr;
        $this->currencyHelper    = $currencyHelper;
    }

    /**
     * GetCustomerFactory
     *
     * @return CustomerFactory
     */
    public function getCustomerFactory(): CustomerFactory
    {
        return $this->customerFactory;
    }

    /**
     * GetCustomerSession
     *
     * @return CustomerSession
     */
    public function getCustomerSession(): CustomerSession
    {
        return $this->customerSession;
    }

    /**
     * GetFilesystem
     *
     * @return Filesystem
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * GetCheckoutSession
     *
     * @return CheckoutSession
     */
    public function getCheckoutSession(): CheckoutSession
    {
        return $this->checkoutSession;
    }

    /**
     * GetGroupRepository
     *
     * @return GroupRepositoryInterface
     */
    public function getGroupRepository(): GroupRepositoryInterface
    {
        return $this->groupRepository;
    }

    /**
     * GetLsr
     *
     * @return LSR
     */
    public function getLsr(): LSR
    {
        return $this->lsr;
    }

    /**
     * GetCurrencyHelper
     *
     * @return Currency
     */
    public function getCurrencyHelper(): Currency
    {
        return $this->currencyHelper;
    }

    /**
     * GetBasketHelper
     *
     * @return BasketHelper
     */
    public function getBasketHelper(): BasketHelper
    {
        return $this->basketHelper;
    }

    /**
     * GetCacheHelper
     *
     * @return CacheHelper
     */
    public function getCacheHelper(): CacheHelper
    {
        return $this->cacheHelper;
    }

    /**
     * GetContactHelper
     *
     * @return ContactHelper
     */
    public function getContactHelper(): ContactHelper
    {
        return $this->contactHelper;
    }

    /**
     * GetDataHelper
     *
     * @return Data
     */
    public function getDataHelper(): Data
    {
        return $this->dataHelper;
    }

    /**
     * GetGiftCardHelper
     *
     * @return GiftCardHelper
     */
    public function getGiftCardHelper(): GiftCardHelper
    {
        return $this->giftCardHelper;
    }

    /**
     * GetItemHelper
     *
     * @return ItemHelper
     */
    public function getItemHelper(): ItemHelper
    {
        return $this->itemHelper;
    }

    /**
     * GetLoyaltyHelper
     *
     * @return LoyaltyHelper
     */
    public function getLoyaltyHelper(): LoyaltyHelper
    {
        return $this->loyaltyHelper;
    }

    /**
     * GetLsRecommendHelper
     *
     * @return LSRecommend
     */
    public function getLsRecommendHelper(): LSRecommend
    {
        return $this->lsRecommendHelper;
    }

    /**
     * GetOrderHelper
     *
     * @return OrderHelper
     */
    public function getOrderHelper(): OrderHelper
    {
        return $this->orderHelper;
    }

    /**
     * GetSessionHelper
     *
     * @return SessionHelper
     */
    public function getSessionHelper(): SessionHelper
    {
        return $this->sessionHelper;
    }

    /**
     * GetStockHelper
     *
     * @return StockHelper
     */
    public function getStockHelper(): StockHelper
    {
        return $this->stockHelper;
    }

    /**
     * GetStoreHelper
     *
     * @return StoreHelper
     */
    public function getStoreHelper(): StoreHelper
    {
        return $this->storeHelper;
    }
}
