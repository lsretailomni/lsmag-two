<?php

namespace Ls\Omni\Helper;

use Laminas\Validator\EmailAddress as ValidateEmailAddress;
use \Ls\Core\Model\LSR;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\Authentication;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\ResourceModel\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;

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
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Configurable $catalogProductTypeConfigurable
     * @param ProductFactory $productFactory
     * @param Registry $registry
     * @param SessionManagerInterface $session
     * @param CartRepositoryInterface $quoteRepository
     * @param Quote $quoteResourceModel
     * @param CartRepositoryInterface $cartRepository
     * @param DateTime $dateTime
     * @param FilterBuilder $filterBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $regionFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param CountryFactory $countryFactory
     * @param CollectionFactory $customerGroupColl
     * @param GroupInterfaceFactory $groupInterfaceFactory
     * @param Customer $customerResourceModel
     * @param Country $country
     * @param RegionFactory $region
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param Wishlist $wishlistResourceModel
     * @param WishlistFactory $wishlistFactory
     * @param CustomerCollection $customerCollection
     * @param EncryptorInterface $encryptorInterface
     * @param ValidateEmailAddress $validateEmailAddress
     * @param CustomerRegistry $customerRegistry
     * @param Authentication $authentication
     * @param AccountConfirmation $accountConfirmation
     * @param ManagerInterface $messageManager
     * @param TimezoneInterface $timezone
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        public Context $context,
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
        public ProductRepositoryInterface $productRepository,
        public SearchCriteriaBuilder $searchCriteriaBuilder,
        public Configurable $catalogProductTypeConfigurable,
        public ProductFactory $productFactory,
        public Registry $registry,
        public SessionManagerInterface $session,
        public CartRepositoryInterface $quoteRepository,
        public Quote $quoteResourceModel,
        public CartRepositoryInterface $cartRepository,
        public DateTime $dateTime,
        public FilterBuilder $filterBuilder,
        public CustomerRepositoryInterface $customerRepository,
        public StoreManagerInterface $storeManager,
        public AddressInterfaceFactory $addressFactory,
        public RegionInterfaceFactory $regionFactory,
        public AddressRepositoryInterface $addressRepository,
        public CountryFactory $countryFactory,
        public CollectionFactory $customerGroupColl,
        public GroupInterfaceFactory $groupInterfaceFactory,
        public Customer $customerResourceModel,
        public Country $country,
        public RegionFactory $region,
        public \Magento\Wishlist\Model\Wishlist $wishlist,
        public Wishlist $wishlistResourceModel,
        public WishlistFactory $wishlistFactory,
        public CustomerCollection $customerCollection,
        public EncryptorInterface $encryptorInterface,
        public ValidateEmailAddress $validateEmailAddress,
        public CustomerRegistry $customerRegistry,
        public Authentication $authentication,
        public AccountConfirmation $accountConfirmation,
        public ManagerInterface $messageManager,
        public TimezoneInterface $timezone,
        public \Magento\Framework\Event\ManagerInterface $eventManager,
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

    public function createInstance(string $entityClassName = null, array $data = [])
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->create($entityClassName, $data);
    }

    /**
     * Flat the given model into serializable array
     *
     * @param DataObject $model
     * @return array
     */
    public function flattenModel(DataObject $model): array
    {
        $data = $model->getData();

        foreach ($data as $key => $value) {
            // Handle nested model
            if ($value instanceof DataObject) {
                $data[$key] = [
                    '__is_model__' => true,
                    '__class__' => get_class($value),
                    'data' => $this->flattenModel($value),
                ];
            } elseif (is_array($value)) {
                $data[$key] = array_map(function ($item) {
                    if ($item instanceof DataObject) {
                        return [
                            '__is_model__' => true,
                            '__class__' => get_class($item),
                            'data' => $this->flattenModel($item),
                        ];
                    }
                    return $item;
                }, $value);
            }
        }

        return [
            '__class__' => get_class($model),
            'data' => $data
        ];
    }

    /**
     * Restore a model from a serialized array
     *
     * @param array $structure
     * @return DataObject
     */
    public function restoreModel(array $structure): DataObject
    {
        $class = $structure['__class__'];
        $rawData = $structure['data'];

        foreach ($rawData as $key => $value) {
            // Handle single nested model
            if (is_array($value) && isset($value['__is_model__'])) {
                $rawData[$key] = $this->restoreModel($value);
            } elseif (is_array($value)) {
                $rawData[$key] = array_map(function ($item) {
                    if (is_array($item) && isset($item['__is_model__'])) {
                        return $this->restoreModel($item);
                    }
                    return $item;
                }, $value);
            }
        }

        /** @var DataObject $model */
        $model = \Magento\Framework\App\ObjectManager::getInstance()->create($class);
        $model->setData($rawData['data'] ?? $rawData);

        return $model;
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
