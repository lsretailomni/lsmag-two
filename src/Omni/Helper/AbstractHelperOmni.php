<?php

namespace Ls\Omni\Helper;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Api\ReplStoreRepositoryInterface;
use \Ls\Replication\Api\ReplStoreTenderTypeRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Currency;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * Abstract Helper for merging common data members and member functions
 */
class AbstractHelperOmni extends AbstractHelper
{
    /**
     * @param Context $context
     * @param BasketHelper $basketHelper
     * @param CacheHelper $cacheHelper
     * @param ContactHelper $contactHelper
     * @param Data $dataHelper
     * @param GiftCardHelper $giftCardHelper
     * @param ItemHelper $itemHelper
     * @param LoyaltyHelper $loyaltyHelper
     * @param OrderHelper $orderHelper
     * @param SessionHelper $sessionHelper
     * @param StockHelper $stockHelper
     * @param StoreHelper $storeHelper
     * @param CustomerFactory $customerFactory
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param Filesystem $filesystem
     * @param GroupRepositoryInterface $groupRepository
     * @param LSR $lsr
     * @param Currency $currencyHelper
     * @param Cart $cart
     * @param ProductRepository $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Configurable $catalogProductTypeConfigurable
     * @param ProductFactory $productFactory
     * @param Registry $registry
     * @param SessionManagerInterface $session
     * @param CartRepositoryInterface $quoteRepository
     * @param Quote $quoteResourceModel
     * @param CartRepositoryInterface $cartRepository
     * @param DateTime $dateTime
     * @param Item $itemResourceModel
     * @param ReplStoreRepositoryInterface $storeRepository
     * @param ManagerInterface $messageManager
     * @param PriceHelper $priceHelper
     * @param WriterInterface $configWriter
     * @param DirectoryList $directoryList
     * @param ReplStoreTenderTypeRepositoryInterface $replStoreTenderTypeRepository
     * @param GetCartForUser $getCartForUser
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param File $fileSystemDriver
     */
    public function __construct(
        Context $context,
        public BasketHelper $basketHelper,
        public CacheHelper $cacheHelper,
        public ContactHelper $contactHelper,
        public Data $dataHelper,
        public GiftCardHelper $giftCardHelper,
        public ItemHelper $itemHelper,
        public LoyaltyHelper $loyaltyHelper,
        public OrderHelper $orderHelper,
        public SessionHelper $sessionHelper,
        public StockHelper $stockHelper,
        public StoreHelper $storeHelper,
        public CustomerFactory $customerFactory,
        public CustomerSession $customerSession,
        public CheckoutSession $checkoutSession,
        public Filesystem $filesystem,
        public GroupRepositoryInterface $groupRepository,
        public LSR $lsr,
        public Currency $currencyHelper,
        public Cart $cart,
        public ProductRepository $productRepository,
        public SearchCriteriaBuilder $searchCriteriaBuilder,
        public Configurable $catalogProductTypeConfigurable,
        public ProductFactory $productFactory,
        public Registry $registry,
        public SessionManagerInterface $session,
        public CartRepositoryInterface $quoteRepository,
        public Quote $quoteResourceModel,
        public CartRepositoryInterface $cartRepository,
        public DateTime $dateTime,
        public Item $itemResourceModel,
        public ReplStoreRepositoryInterface $storeRepository,
        public ManagerInterface $messageManager,
        public PriceHelper $priceHelper,
        public WriterInterface $configWriter,
        public DirectoryList $directoryList,
        public ReplStoreTenderTypeRepositoryInterface $replStoreTenderTypeRepository,
        public GetCartForUser $getCartForUser,
        public MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        public File $fileSystemDriver
    ) {
        parent::__construct($context);
        $this->initialize();
    }

    /**
     * Initialize specific properties
     *
     * @return void
     */
    public function initialize(): void
    {
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
