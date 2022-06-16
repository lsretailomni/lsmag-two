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
}
