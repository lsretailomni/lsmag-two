<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Checkout;

use GuzzleHttp\Exception\GuzzleException;
use Laminas\Json\Json;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Block\Stores\Stores;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetStores_GetStores;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootGetInventoryMultipleOut;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\StockHelper;
use \Ls\Omni\Helper\StoreHelper;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Custom Data provider
 */
class DataProvider implements ConfigProviderInterface
{
    public const XPATH_MAPS_API_KEY = 'omni_clickandcollect/general/maps_api_key';
    public const XPATH_DEFAULT_LATITUDE = 'omni_clickandcollect/general/default_latitude';
    public const XPATH_DEFAULT_LONGITUDE = 'omni_clickandcollect/general/default_longitude';
    public const XPATH_DEFAULT_ZOOM = 'omni_clickandcollect/general/default_zoom';
    public const XPATH_CHECKOUT_ITEM_AVAILABILITY = 'omni_clickandcollect/checkout/items_availability';

    /**
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $storeCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param LSR $lsr
     * @param Session $checkoutSession
     * @param StockHelper $stockHelper
     * @param StoreHelper $storeHelper
     * @param GiftCardHelper $giftCardHelper
     * @param BasketHelper $basketHelper
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        public StoreManagerInterface $storeManager,
        public CollectionFactory $storeCollectionFactory,
        public ScopeConfigInterface $scopeConfig,
        public LSR $lsr,
        public Session $checkoutSession,
        public StockHelper $stockHelper,
        public StoreHelper $storeHelper,
        public GiftCardHelper $giftCardHelper,
        public BasketHelper $basketHelper,
        public LayoutFactory $layoutFactory
    ) {
    }

    /**
     * Get config
     *
     * This function is overriding in hospitality module
     *
     * @return array
     * @throws NoSuchEntityException|LocalizedException|InvalidEnumException|GuzzleException
     */
    public function getConfig()
    {
        $config = [];

        if ($this->isValid()) {
            $clickAndCollectEnabled = $this->lsr->getClickCollectEnabled();

            if ($clickAndCollectEnabled) {
                $store = $this->getStoreId();
                $mapsApiKey = $this->scopeConfig->getValue(
                    self::XPATH_MAPS_API_KEY,
                    ScopeInterface::SCOPE_STORE,
                    $store
                );
                $defaultLatitude = $this->scopeConfig->getValue(
                    self::XPATH_DEFAULT_LATITUDE,
                    ScopeInterface::SCOPE_STORE,
                    $store
                );
                $defaultLongitude = $this->scopeConfig->getValue(
                    self::XPATH_DEFAULT_LONGITUDE,
                    ScopeInterface::SCOPE_STORE,
                    $store
                );
                $defaultZoom = $this->scopeConfig->getValue(
                    self::XPATH_DEFAULT_ZOOM,
                    ScopeInterface::SCOPE_STORE,
                    $store
                );

                $storesResponse = $this->getStores();
                $layout = $this->layoutFactory->create();
                $storesData = $layout->createBlock(Stores::class)
                    ->setTemplate('Ls_Omni::stores/stores.phtml')
                    ->setData('data', $storesResponse)
                    ->setData('storeHours', 0)
                    ->toHtml();
                $stores = $storesResponse ? $storesResponse->toArray() : [];
                $stores['storesInfo'] = $storesData;
                $encodedStores = Json::encode($stores);

                $config['shipping']['select_store'] = [
                    'maps_api_key' => $mapsApiKey,
                    'lat' => (float)$defaultLatitude,
                    'lng' => (float)$defaultLongitude,
                    'zoom' => (int)$defaultZoom,
                    'stores' => $encodedStores,
                    'available_store_only' => (bool)$this->availableStoresOnlyEnabled()
                ];
            }

            if ($this->lsr->getClickCollectEnabled() || $this->lsr->getFlatRateEnabled()) {
                $this->setRespectiveTimeSlotsInCheckoutSession();
            }

            $enabled = $this->lsr->isPickupTimeslotsEnabled();
            $deliveryHoursEnabled = $this->lsr->isDeliveryTimeslotsEnabled();

            if (empty($this->basketHelper->getStorePickUpHoursFromCheckoutSession()) || !$clickAndCollectEnabled) {
                $enabled = 0;
            }

            if (empty($this->basketHelper->getDeliveryHoursFromCheckoutSession())) {
                $deliveryHoursEnabled = 0;
            }
            $config['shipping']['pickup_date_timeslots'] = [
                'options' => $this->basketHelper->getStorePickUpHoursFromCheckoutSession(),
                'enabled' => $enabled,
                'current_web_store' => $this->lsr->getActiveWebStore(),
                'store_type' => 0,
                'delivery_hours' => $this->basketHelper->getDeliveryHoursFromCheckoutSession(),
                'delivery_hours_enabled' => $deliveryHoursEnabled
            ];
            $config['coupons_display'] = $this->isCouponsDisplayEnabled();
        }

        $config['ls_enabled'] = (bool)$this->lsr->isEnabled();

        $config['gift_card_pin_enable'] = $this->giftCardHelper->isPinCodeFieldEnable();

        return $config;
    }

    /**
     * Is valid
     *
     * @return bool|null
     * @throws NoSuchEntityException|GuzzleException
     */
    public function isValid()
    {
        return $this->lsr->isLSR($this->lsr->getCurrentStoreId());
    }

    /**
     * Get store Id
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }

    /**
     * Get stores
     *
     * @return Collection
     * @throws NoSuchEntityException|LocalizedException|InvalidEnumException
     */
    public function getStores()
    {
        $storesData = $this->getRequiredStores();

        if (!$this->availableStoresOnlyEnabled()) {
            return $storesData;
        }
        $this->checkoutSession->setNoManageStock(0);
        $items = $this->checkoutSession->getQuote()->getAllVisibleItems();
        list($response) = $this->stockHelper->getGivenItemsStockInGivenStore($items);

        if ($response && !empty($response->getInventorybufferout())) {

            $clickNCollectStoresIds = $this->getClickAndCollectStoreIds($storesData);
            $this->filterClickAndCollectStores($response, $clickNCollectStoresIds);

            return $this->filterStoresOnTheBasisOfQty($response, $items);
        }

        return null;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Get all click and collect stores
     *
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function getRequiredStores()
    {
        return $this->storeCollectionFactory->create()
            ->addFieldToFilter(
                'scope_id',
                !$this->lsr->isSSM() ?
                    $this->lsr->getCurrentWebsiteId() :
                    $this->lsr->getAdminStore()->getWebsiteId()
            )->addFieldToFilter('ClickAndCollect', 1);
    }

    /**
     * Set both pick up and delivery calenders in checkout session based on configurations
     *
     * @return void
     * @throws InvalidEnumException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function setRespectiveTimeSlotsInCheckoutSession()
    {
        if ($this->lsr->isPickupTimeslotsEnabled() || $this->lsr->isDeliveryTimeslotsEnabled()) {
            $allStores = $this->storeHelper->getAllStoresFromCentral();

            if ($this->lsr->isPickupTimeslotsEnabled()) {
                $storeHoursArray = $this->getRelevantStoreHours(null, $allStores);

                if (!empty($storeHoursArray)) {
                    $this->basketHelper->setStorePickUpHoursInCheckoutSession($storeHoursArray);
                }
            }

            if ($this->lsr->isDeliveryTimeslotsEnabled()) {
                $deliveryHoursArray = $this->getRelevantStoreHours(2, $allStores);

                if (!empty($deliveryHoursArray)) {
                    $this->basketHelper->setDeliveryHoursInCheckoutSession($deliveryHoursArray);
                }
            }
        }
    }

    /**
     * Get relevant store hours
     *
     * @param ?int $calendarType
     * @param ?GetStores_GetStores $allStores
     * @return array
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     * @throws GuzzleException
     */
    public function getRelevantStoreHours($calendarType = null, $allStores = null)
    {
        $storeHoursArray = [];

        if ($allStores == null) {
            $allStores = $this->storeHelper->getAllStoresFromCentral();
        }

        foreach (!empty($allStores->getLscStore()) ?  $allStores->getLscStore() : [] as $store) {
            if ($store->getClickAndCollect() || $store->getWebStore()) {
                $calendarLines = $extraCalendarLines = [];
                foreach (!empty($allStores->getLscRetailCalendarLine()) ?
                    $allStores->getLscRetailCalendarLine() : [] as $calendarLine) {

                    if ($store->getNo() == $calendarLine->getCalendarId() &&
                        (!$calendarType || $calendarType == $calendarLine->getCalendarType()
                    )) {
                        $calendarLines[] = $calendarLine;
                    }
                }

                if (!empty($calendarLines)) {
                    $storeHoursArray[$store->getNo()] = $this->storeHelper->formatDateTimeSlotsValues(
                        $calendarLines,
                        $calendarType
                    );
                }

                if (empty($storeHoursArray[$store->getNo()])) {
                    foreach (!empty($allStores->getLscRtlCalendarGroupLinking()) ?
                        $allStores->getLscRtlCalendarGroupLinking() : [] as $storeLink) {
                        if ($store->getNo() == $storeLink->getStoreNo() && !empty($storeLink->getCalendarId())) {
                            $currentCalendarType = $storeLink->getCalendarType();
                            $currentCalendarId =  $storeLink->getCalendarId();
                            foreach (!empty($allStores->getLscRetailCalendarLine()) ?
                                $allStores->getLscRetailCalendarLine() : [] as $calendarLine) {
                                if ((!$calendarType || $calendarType == $calendarLine->getCalendarType()) &&
                                    $calendarLine->getCalendarType() == $currentCalendarType &&
                                    $calendarLine->getCalendarId() == $currentCalendarId
                                ) {
                                    $extraCalendarLines[] = $calendarLine;
                                }
                            }
                            if (!empty($extraCalendarLines)) {
                                $storeHoursArray[$store->getNo()] = $this->storeHelper->formatDateTimeSlotsValues(
                                    $extraCalendarLines,
                                    $calendarType
                                );
                            }
                        }
                    }
                }
            }
        }

        return $storeHoursArray;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Available Stores only enabled
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function availableStoresOnlyEnabled()
    {
        return $this->scopeConfig->getValue(
            self::XPATH_CHECKOUT_ITEM_AVAILABILITY,
            ScopeInterface::SCOPE_STORE,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * Get click and collect store ids
     *
     * @param Collection $storesData
     * @return array
     */
    public function getClickAndCollectStoreIds(Collection $storesData)
    {
        $clickNCollectStoresIds = [];

        foreach ($storesData->getItems() as $storeData) {
            $clickNCollectStoresIds[] = $storeData->getNavId();
        }

        return $clickNCollectStoresIds;
    }

    /**
     * Filter Click And Collect Stores
     *
     * @param RootGetInventoryMultipleOut $response
     * @param array $clickNCollectStoresIds
     */
    public function filterClickAndCollectStores(&$response, array $clickNCollectStoresIds)
    {
        $inventoryRecords = $response->getInventorybufferout();

        foreach ($inventoryRecords as $index => $item) {
            if ($item && !in_array($item->getStore(), $clickNCollectStoresIds)) {
                unset($inventoryRecords[$index]);
            }
        }

        $response->setInventorybufferout($inventoryRecords);
    }

    /**
     * Filter Stores on the basis of quantity
     *
     * @param RootGetInventoryMultipleOut $response
     * @param array $items
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function filterStoresOnTheBasisOfQty(RootGetInventoryMultipleOut $response, array $items)
    {
        foreach ($items as $item) {
            $children = [];
            if ($item->getProductType() == Type::TYPE_BUNDLE) {
                $children = $item->getChildren();
            } else {
                $children[] = $item;
            }

            foreach ($children as $child) {
                $itemQty = $item->getQty();
                list($parentProductSku, $childProductSku, , , $uomQty) =
                    $this->stockHelper->itemHelper->getComparisonValues($child->getSku());

                if (!empty($uomQty)) {
                    $itemQty = $itemQty * $uomQty;
                }
                if ($response) {
                    foreach ($response->getInventorybufferout() as $responseItem) {
                        if ($responseItem->getNumber() == $parentProductSku &&
                            $responseItem->getVariant() == $childProductSku &&
                            ceil($responseItem->getInventory()) < $itemQty
                        ) {
                            $this->removeAllOccurrenceOfGivenStore($response, $responseItem->getStore());
                        }
                    }
                }
            }
        }

        $responseItems = null;

        if (!empty($response)) {
            $responseItems = $this->getAllResponseItemsWithInventoryAvailable($response);
        }

        return $this->getSelectedClickAndCollectStoresData($responseItems);
    }

    /**
     * Remove all occurrence of given store
     *
     * @param RootGetInventoryMultipleOut $response
     * @param string $storeId
     */
    public function removeAllOccurrenceOfGivenStore(&$response, string $storeId)
    {
        $inventoryRecords = $response->getInventorybufferout();

        foreach ($inventoryRecords as $index => $responseItem) {
            if ($responseItem->getStore() == $storeId) {
                unset($inventoryRecords[$index]);
            }
        }

        $response->setInventorybufferout($inventoryRecords);
    }

    /**
     * Get all response items with inventory available
     *
     * @param RootGetInventoryMultipleOut $response
     * @return array
     */
    public function getAllResponseItemsWithInventoryAvailable(RootGetInventoryMultipleOut $response)
    {
        $responseItems = [];

        foreach ($response->getInventorybufferout() as $responseItem) {
            $responseItems[] = $responseItem->getStore();
        }

        return array_unique($responseItems);
    }

    /**
     * Get selected click and collect stores Data
     *
     * @param array $responseItems
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function getSelectedClickAndCollectStoresData(array $responseItems)
    {
        return $this->storeCollectionFactory
            ->create()
            ->addFieldToFilter(
                'scope_id',
                !$this->lsr->isSSM() ?
                    $this->lsr->getCurrentWebsiteId() :
                    $this->lsr->getAdminStore()->getWebsiteId()
            )
            ->addFieldToFilter('nav_id', ['in' => $responseItems]);
    }

    /**
     * Is coupons display enabled
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function isCouponsDisplayEnabled()
    {
        return ($this->lsr->getStoreConfig(
            LSR::LS_ENABLE_COUPON_ELEMENTS,
            $this->lsr->getCurrentStoreId()
        ) &&
            $this->lsr->getStoreConfig(
                LSR::LS_COUPON_RECOMMENDATIONS_SHOW_ON_CART_CHECKOUT,
                $this->lsr->getCurrentStoreId()
            )
        );
    }
}
