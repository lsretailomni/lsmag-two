<?php

namespace Ls\OmniGraphQl\Helper;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DiscountType;
use \Ls\Omni\Client\Ecommerce\Entity\PublishedOffer;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\StockHelper;
use \Ls\Omni\Helper\StoreHelper;
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use \Ls\Omni\Model\Checkout\DataProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Useful helper functions for the module
 *
 */
class DataHelper extends AbstractHelper
{
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /** @var CustomerRepositoryInterface */
    public $customerRepository;

    /** @var CustomerFactory */
    public $customerFactory;

    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @var Data
     */
    public $omniDataHelper;

    /** @var CollectionFactory */
    public $storeCollectionFactory;

    /**
     * @var GetCartForUser
     */
    public $getCartForUser;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    public $maskedQuoteIdToQuoteId;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @var StockHelper
     */
    public $stockHelper;

    /**
     * @var StoreHelper
     */
    public $storeHelper;

    /**
     * @var Information
     */
    public $storeInfo;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /** @var AddressInterfaceFactory */
    public $addressFactory;

    /** @var DataProvider */
    public DataProvider $dataProvider;
    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timeZoneInterface;
    /**
     * @var LSR
     */
    private LSR $lsr;

    /**
     * @param Context $context
     * @param ManagerInterface $eventManager
     * @param BasketHelper $basketHelper
     * @param CheckoutSession $checkoutSession
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerFactory $customerFactory
     * @param Session $customerSession
     * @param Data $omniDataHelper
     * @param CollectionFactory $storeCollectionFactory
     * @param GetCartForUser $getCartForUser
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     * @param StockHelper $stockHelper
     * @param StoreHelper $storeHelper
     * @param Information $storeInfo
     * @param StoreManagerInterface $storeManager
     * @param AddressInterfaceFactory $addressFactory
     * @param DataProvider $dataProvider
     * @param TimezoneInterface $timeZoneInterface
     * @param LSR $lsr
     */
    public function __construct(
        Context $context,
        ManagerInterface $eventManager,
        BasketHelper $basketHelper,
        CheckoutSession $checkoutSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        Session $customerSession,
        Data $omniDataHelper,
        CollectionFactory $storeCollectionFactory,
        GetCartForUser $getCartForUser,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository,
        StockHelper $stockHelper,
        StoreHelper $storeHelper,
        Information $storeInfo,
        StoreManagerInterface $storeManager,
        AddressInterfaceFactory $addressFactory,
        DataProvider $dataProvider,
        TimezoneInterface $timeZoneInterface,
        LSR $lsr
    ) {
        parent::__construct($context);
        $this->eventManager           = $eventManager;
        $this->basketHelper           = $basketHelper;
        $this->checkoutSession        = $checkoutSession;
        $this->searchCriteriaBuilder  = $searchCriteriaBuilder;
        $this->orderRepository        = $orderRepository;
        $this->customerRepository     = $customerRepository;
        $this->customerFactory        = $customerFactory;
        $this->customerSession        = $customerSession;
        $this->omniDataHelper         = $omniDataHelper;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->getCartForUser         = $getCartForUser;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository         = $cartRepository;
        $this->stockHelper            = $stockHelper;
        $this->storeHelper            = $storeHelper;
        $this->storeInfo              = $storeInfo;
        $this->storeManager           = $storeManager;
        $this->addressFactory         = $addressFactory;
        $this->dataProvider           = $dataProvider;
        $this->timeZoneInterface      = $timeZoneInterface;
        $this->lsr                    = $lsr;
    }

    /**
     * Setting quote id and ls_one_list in the session and calling the required event
     * @param $quote
     * @return CartInterface|Quote
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function triggerEventForCartChange($quote)
    {
        if ($quote->getBasketResponse()) {
            $this->basketHelper->setOneListCalculationInCheckoutSession(
                unserialize($quote->getBasketResponse())
            );
        }

        /**
         * Clearing the quote from session just in case if someone did $this->checkoutSession->getQuote()
         * before $this->checkoutSession->setQuoteId($quote->getId());
         **/
        $this->checkoutSession->clearQuote();
        $this->checkoutSession->setQuoteId($quote->getId());
        $this->eventManager->dispatch('checkout_cart_save_after');

        return $this->checkoutSession->getQuote();
    }

    /**
     * Gives order based on the given increment_id
     * @param $incrementId
     * @return OrderInterface
     */
    public function getOrderByIncrementId($incrementId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId)->create()
            ->setPageSize(1)->setCurrentPage(1);
        $orderData      = null;
        $order          = $this->orderRepository->getList($searchCriteria);

        if ($order->getTotalCount()) {
            $orderData = current($order->getItems());
        }

        return $orderData;
    }

    /**
     * Setting required values in the customer session that will be used later
     * @param int $customerId
     * @param int $websiteId
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCustomerValuesInSession($customerId = 0, $websiteId = 0)
    {
        if ($customerId === 0) {
            return;
        }

        $customer = $this->customerRepository->getById($customerId);
        $customer = $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->loadByEmail($customer->getEmail());
        $this->customerSession->setCustomer($customer);
        //$this->customerSession->setCustomerAsLoggedIn($customer)

        $this->customerSession->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $customer->getData('lsr_token'));
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $customer->getData('lsr_id'));
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
    }

    /**
     * Getting customer session
     * @return Session
     */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * Format given store data for better understandability
     *
     * @param array $store
     * @return array
     */
    public function formatStoreData($store)
    {
        return [
            'store_id'                          => $store['nav_id'],
            'store_name'                        => $store['Name'],
            'click_and_collect_accepted'        => $store['ClickAndCollect'],
            'latitude'                          => $store['Latitute'],
            'longitude'                         => $store['Longitude'],
            'phone'                             => $store['Phone'],
            'city'                              => $store['City'],
            'country'                           => $store['Country'],
            'county'                            => $store['County'],
            'state'                             => $store['State'],
            'zip_code'                          => $store['ZipCode'],
            'currency_accepted'                 => $store['Currency'],
            'street'                            => $store['Street'],
            'available_hospitality_sales_types' =>
                $store['HospSalesTypes'] ? explode('|', $store['HospSalesTypes']) : null,
            'store_hours'                       => $this->formatStoreTiming($store['nav_id'])
        ];
    }

    /**
     * Format store timing
     *
     * @param string $storeId
     * @return array
     */
    public function formatStoreTiming($storeId)
    {
        $storeHours = $this->omniDataHelper->getStoreHours($storeId);
        $hours      = [];
        $i          = 0;

        $hoursFormat = $this->scopeConfig->getValue(
            LSR::LS_STORES_OPENING_HOURS_FORMAT,
            ScopeInterface::SCOPE_STORE
        );

        foreach ($storeHours as $storeHour) {
            $normalTypeOpenHours = $normalTypeCloseHours = $closedTypeOpenHours = $closedTypeCloseHours = null;
            foreach ($storeHour as $key => $hour) {
                $hours[$i]['day_of_week'] = $hour['day'];
                if ($hour['type'] == "Normal") {
                    $normalTypeOpenHours  = date($hoursFormat, strtotime($hour['open']));
                    $normalTypeCloseHours = date($hoursFormat, strtotime($hour['close']));
                } elseif ($hour['type'] == "Closed") {
                    $closedTypeOpenHours  = date($hoursFormat, strtotime($hour['open']));
                    $closedTypeCloseHours = date($hoursFormat, strtotime($hour['close']));
                }

                if ($normalTypeOpenHours && $closedTypeOpenHours
                    && ($normalTypeOpenHours == $closedTypeOpenHours)
                    && ($normalTypeCloseHours == $closedTypeCloseHours)
                ) {
                    $hours[$i]['hour_types'][0] = $this->formatHoursAccordingToType($hour);
                } else {
                    $hours[$i]['hour_types'][$key] = $this->formatHoursAccordingToType($hour);
                }
            }
            $i++;
        }

        return $hours;
    }

    /**
     * Format hours according to their type
     *
     * @param array $hour
     * @return array
     */
    public function formatHoursAccordingToType($hour)
    {
        $hoursFormat = $this->scopeConfig->getValue(
            LSR::LS_STORES_OPENING_HOURS_FORMAT,
            ScopeInterface::SCOPE_STORE
        );

        return [
            'type'         => $hour['type'],
            'opening_time' => date($hoursFormat, strtotime($hour['open'])),
            'closing_time' => date($hoursFormat, strtotime($hour['close']))
        ];
    }

    /**
     * Get all click and collect supported stores for given scope_id
     *
     * @param String $scopeId
     * @return Collection
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getStores($scopeId)
    {
        $storeCollection = $this->storeCollectionFactory->create();

        $storesData = $storeCollection
            ->addFieldToFilter('scope_id', $scopeId)
            ->addFieldToFilter('ClickAndCollect', 1);

        if (!$this->dataProvider->availableStoresOnlyEnabled()) {
            return $storesData;
        }

        $itemsCount = $this->checkoutSession->getQuote()->getItemsCount();
        if ($itemsCount > 0) {
            $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
            list($response) = $this->stockHelper->getGivenItemsStockInGivenStore($items);

            if ($response) {
                if (is_object($response)) {
                    if (!is_array($response->getInventoryResponse())) {
                        $response = [$response->getInventoryResponse()];
                    } else {
                        $response = $response->getInventoryResponse();
                    }
                }

                $clickNCollectStoresIds = $this->dataProvider->getClickAndCollectStoreIds($storesData);
                $this->dataProvider->filterClickAndCollectStores($response, $clickNCollectStoresIds);

                return $this->dataProvider->filterStoresOnTheBasisOfQty($response, $items);
            }
        }

        return $storesData;
    }

    /**
     * Set pickup store given cart
     *
     * @param mixed $cart
     * @param String $pickupStore
     * @param String $pickupDate
     * @param String $pickupTimeslot
     * @return void
     */
    public function setPickUpStoreGivenCart(&$cart, $pickupStore, $pickupDate, $pickupTimeslot)
    {
        $pickupDateTimeslot = $this->basketHelper->getPickupTimeSlot($pickupDate, $pickupTimeslot);

        if (!empty($pickupDateTimeslot)) {
            $cart->setPickupDateTimeslot($pickupDateTimeslot);
        }

        $cart->setPickupStore($pickupStore);

        $this->cartRepository->save($cart);
    }

    /**
     * Get order taking calendar given store id and website id
     *
     * @param String $storeId
     * @param String $websiteId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getOrderTakingCalendarGivenStoreId($storeId, $websiteId)
    {
        $store         = $this->storeHelper->getStore($websiteId, $storeId);
        $slots         = $this->storeHelper->formatDateTimeSlotsValues($store->getStoreHours());
        $formattedData = [];

        foreach ($slots as $index => $slot) {
            $formattedData[] = ['date' => $index, 'slots' => $slot];
        }

        return $formattedData;
    }

    /**
     * Get cart model given required data
     *
     * @param String $maskedCartId
     * @param String $userId
     * @param String $storeId
     * @return Quote
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function getCartGivenRequiredData($maskedCartId, $userId, $storeId)
    {
        return $this->getCartForUser->execute($maskedCartId, $userId, $storeId);
    }

    /**
     * Get store information
     *
     * @return DataObject
     * @throws NoSuchEntityException
     */
    public function getStoreInformation()
    {
        $store = $this->storeManager->getStore();

        return $this->storeInfo->getStoreInformationObject($store);
    }

    /**
     * Get anonymous address
     *
     * @return AddressInterface
     * @throws NoSuchEntityException
     */
    public function getAnonymousAddress()
    {
        $storeInformation = $this->getStoreInformation();
        $streets          = [$storeInformation->getData('street_line1')];

        if ($storeInformation->getData('street_line2')) {
            $streets[] = $storeInformation->getData('street_line2');
        }

        $address = $this->addressFactory->create();
        $address->setFirstname($storeInformation->getName())
            ->setLastname($storeInformation->getName())
            ->setCountryId($storeInformation->getCountryId())
            ->setPostcode($storeInformation->getPostcode())
            ->setRegionId($storeInformation->getRegionId())
            ->setCity($storeInformation->getCity())
            ->setTelephone($storeInformation->getPhone())
            ->setStreet($streets)
            ->setShippingMethod('flatrate_flatrate');

        return $address;
    }

    /**
     * Get customer email for anonymous orders
     *
     * @return mixed
     */
    public function getAnonymousOrderCustomerEmail()
    {
        return $this->scopeConfig->getValue(
            'trans_email/ident_custom1/email',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Store Name by Id
     *
     * @param string $storeId
     * @return mixed|string
     */
    public function getStoreNameById($storeId)
    {
        return $this->omniDataHelper->getStoreNameById($storeId);
    }

    /**
     * Format coupon code response
     *
     * @param PublishedOffer $coupon
     * @return array|string
     */
    public function getFormattedDescriptionCoupon(PublishedOffer $coupon)
    {
        $responseArr = [];
        if ($coupon->getDescription()) {
            $responseArr['coupon_description'] = $coupon->getDescription();
        }
        if ($coupon->getDetails()) {
            $responseArr['coupon_details'] = $coupon->getDetails();
        }
        if ($coupon->getCode() != DiscountType::PROMOTION) {
            if ($coupon->getExpirationDate()) {
                $responseArr['coupon_expire_date'] = $this->getFormattedOfferExpiryDate($coupon->getExpirationDate());
            }
            if ($coupon->getOfferId()) {
                $responseArr['offer_id'] = $coupon->getOfferId();
            }
        }

        return $responseArr;
    }

    /**
     * Get formatted expiry date
     *
     * @param $date
     * @return string
     */
    public function getFormattedOfferExpiryDate($date)
    {
        try {
            return $this->timeZoneInterface->date($date)->format($this->scopeConfig->getValue(
                LSR::SC_LOYALTY_EXPIRY_DATE_FORMAT,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $this->lsr->getActiveWebStore()
            ));

        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return null;
    }
}
