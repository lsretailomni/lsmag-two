<?php

namespace Ls\Omni\Model\Checkout;

use Laminas\Json\Json;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Block\Stores\Stores;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\StoreHourCalendarType;
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
    const XPATH_MAPS_API_KEY = 'omni_clickandcollect/general/maps_api_key';
    const XPATH_DEFAULT_LATITUDE = 'omni_clickandcollect/general/default_latitude';
    const XPATH_DEFAULT_LONGITUDE = 'omni_clickandcollect/general/default_longitude';
    const XPATH_DEFAULT_ZOOM = 'omni_clickandcollect/general/default_zoom';
    const XPATH_CHECKOUT_ITEM_AVAILABILITY = 'omni_clickandcollect/checkout/items_availability';

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var CollectionFactory */
    public $storeCollectionFactory;

    /** @var ScopeConfigInterface */
    public $scopeConfig;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * @var StockHelper
     */
    public $stockHelper;

    /**
     * @var StoreHelper
     */
    public $storeHelper;

    /**
     * @var GiftCardHelper
     */
    public $giftCardHelper;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var LayoutFactory
     */
    public $layoutFactory;

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
        StoreManagerInterface $storeManager,
        CollectionFactory $storeCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        LSR $lsr,
        Session $checkoutSession,
        StockHelper $stockHelper,
        StoreHelper $storeHelper,
        GiftCardHelper $giftCardHelper,
        BasketHelper $basketHelper,
        LayoutFactory $layoutFactory
    ) {
        $this->storeManager           = $storeManager;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->scopeConfig            = $scopeConfig;
        $this->lsr                    = $lsr;
        $this->checkoutSession        = $checkoutSession;
        $this->stockHelper            = $stockHelper;
        $this->storeHelper            = $storeHelper;
        $this->giftCardHelper         = $giftCardHelper;
        $this->basketHelper           = $basketHelper;
        $this->layoutFactory          = $layoutFactory;
    }

    /**
     * Get config
     *
     * This function is overriding in hospitality module
     *
     * @return array
     * @throws NoSuchEntityException|LocalizedException|InvalidEnumException
     */
    public function getConfig()
    {
        $config = [];

        if ($this->isValid()) {
            $clickAndCollectEnabled = $this->lsr->getClickCollectEnabled();

            if ($clickAndCollectEnabled) {
                $store                = $this->getStoreId();
                $mapsApiKey           = $this->scopeConfig->getValue(
                    self::XPATH_MAPS_API_KEY,
                    ScopeInterface::SCOPE_STORE,
                    $store
                );
                $defaultLatitude      = $this->scopeConfig->getValue(
                    self::XPATH_DEFAULT_LATITUDE,
                    ScopeInterface::SCOPE_STORE,
                    $store
                );
                $defaultLongitude     = $this->scopeConfig->getValue(
                    self::XPATH_DEFAULT_LONGITUDE,
                    ScopeInterface::SCOPE_STORE,
                    $store
                );
                $defaultZoom          = $this->scopeConfig->getValue(
                    self::XPATH_DEFAULT_ZOOM,
                    ScopeInterface::SCOPE_STORE,
                    $store
                );

                $storesResponse       = $this->getStores();
                $layout               = $this->layoutFactory->create();
                $storesData           = $layout->createBlock(Stores::class)
                    ->setTemplate('Ls_Omni::stores/stores.phtml')
                    ->setData('data', $storesResponse)
                    ->setData('storeHours', 0)
                    ->toHtml();
                $stores = $storesResponse ? $storesResponse->toArray() : [];
                $stores['storesInfo'] = $storesData;
                $encodedStores        = Json::encode($stores);

                $config['shipping']['select_store'] = [
                    'maps_api_key'         => $mapsApiKey,
                    'lat'                  => (float)$defaultLatitude,
                    'lng'                  => (float)$defaultLongitude,
                    'zoom'                 => (int)$defaultZoom,
                    'stores'               => $encodedStores,
                    'available_store_only' => $this->availableStoresOnlyEnabled()
                ];
            }

            if ($this->lsr->getClickCollectEnabled() || $this->lsr->getFlatRateEnabled()) {
                $this->setRespectiveTimeSlotsInCheckoutSession();
            }

            $enabled              = $this->lsr->isPickupTimeslotsEnabled();
            $deliveryHoursEnabled = $this->lsr->isDeliveryTimeslotsEnabled();

            if (empty($this->basketHelper->getStorePickUpHoursFromCheckoutSession()) || !$clickAndCollectEnabled) {
                $enabled = 0;
            }

            if (empty($this->basketHelper->getDeliveryHoursFromCheckoutSession())) {
                $deliveryHoursEnabled = 0;
            }
            $config['shipping']['pickup_date_timeslots'] = [
                'options'                => $this->basketHelper->getStorePickUpHoursFromCheckoutSession(),
                'enabled'                => $enabled,
                'current_web_store'      => $this->lsr->getActiveWebStore(),
                'store_type'             => 0,
                'delivery_hours'         => $this->basketHelper->getDeliveryHoursFromCheckoutSession(),
                'delivery_hours_enabled' => $deliveryHoursEnabled
            ];
            $config['coupons_display'] = $this->isCouponsDisplayEnabled();
        }

        $config['ls_enabled'] = (bool)$this->lsr->isEnabled();

        $config['gift_card_pin_enable'] = (bool)$this->giftCardHelper->isPinCodeFieldEnable();

        return $config;
    }

    /**
     * Is valid
     *
     * @return bool|null
     * @throws NoSuchEntityException
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

        if ($response) {
            if (is_object($response)) {
                if (!is_array($response->getInventoryResponse())) {
                    $response = [$response->getInventoryResponse()];
                } else {
                    $response = $response->getInventoryResponse();
                }
            }

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
     * @return Collection|null
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
     * @throws NoSuchEntityException
     */
    public function setRespectiveTimeSlotsInCheckoutSession()
    {
        if ($this->lsr->isPickupTimeslotsEnabled() || $this->lsr->isDeliveryTimeslotsEnabled()) {
            $allStores = $this->storeHelper->getAllStores(
                !$this->lsr->isSSM() ?
                    $this->lsr->getCurrentStoreId() :
                    $this->lsr->getAdminStore()->getId()
            );

            if ($this->lsr->isPickupTimeslotsEnabled()) {
                $storeHoursArray = $this->getRelevantStoreHours(null, $allStores);

                if (!empty($storeHoursArray)) {
                    $this->basketHelper->setStorePickUpHoursInCheckoutSession($storeHoursArray);
                }
            }

            if ($this->lsr->isDeliveryTimeslotsEnabled()) {
                $deliveryHoursArray = $this->getRelevantStoreHours(StoreHourCalendarType::RECEIVING, $allStores);

                if (!empty($deliveryHoursArray)) {
                    $this->basketHelper->setDeliveryHoursInCheckoutSession($deliveryHoursArray);
                }
            }
        }
    }

    /**
     * Get relevant store hours
     *
     * @param null $calendarType
     * @param null $allStores
     * @return array
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getRelevantStoreHours($calendarType = null, $allStores = null)
    {
        $storeHoursArray = [];

        if ($allStores == null) {
            $allStores = $this->storeHelper->getAllStores(
                !$this->lsr->isSSM() ?
                    $this->getStoreId() :
                    $this->lsr->getAdminStore()->getId()
            );
        }

        foreach ($allStores as $store) {
            if ($store->getIsClickAndCollect() || $store->getIsWebStore()) {
                $storeHoursArray[$store->getId()] = $this->storeHelper->formatDateTimeSlotsValues(
                    $store->getStoreHours(),
                    $calendarType
                );
            }
        }

        return $storeHoursArray;
    }

    /**
     * This function is overriding in hospitality module
     *
     * Available Stores only enabled
     *
     * @return mixed
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
     * @param $storesData
     * @return array
     */
    public function getClickAndCollectStoreIds($storesData)
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
     * @param $response
     * @param $clickNCollectStoresIds
     */
    public function filterClickAndCollectStores(&$response, $clickNCollectStoresIds)
    {
        if (!empty($response)) {
            foreach ($response as $index => $item) {
                if (!in_array($item->getStoreId(), $clickNCollectStoresIds)) {
                    unset($response[$index]);
                }
            }
        }
    }

    /**
     * Filter Stores on the basis of quantity
     *
     * @param $response
     * @param $items
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function filterStoresOnTheBasisOfQty($response, $items)
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
                    foreach ($response as $responseItem) {
                        if ($responseItem->getItemId() == $parentProductSku &&
                            $responseItem->getVariantId() == $childProductSku &&
                            ceil($responseItem->getQtyInventory()) < $itemQty
                        ) {
                            $this->removeAllOccurrenceOfGivenStore($response, $responseItem->getStoreId());
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
     * @param $response
     * @param $storeId
     */
    public function removeAllOccurrenceOfGivenStore(&$response, $storeId)
    {
        foreach ($response as $index => $responseItem) {
            if ($responseItem->getStoreId() == $storeId) {
                unset($response[$index]);
            }
        }
    }

    /**
     * Get all response items with inventory available
     *
     * @param $response
     * @return array
     */
    public function getAllResponseItemsWithInventoryAvailable($response)
    {
        $responseItems = [];

        foreach ($response as $responseItem) {
            $responseItems[] = $responseItem->getStoreId();
        }

        return array_unique($responseItems);
    }

    /**
     * Get selected click and collect stores Data
     *
     * @param $responseItems
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function getSelectedClickAndCollectStoresData($responseItems)
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
