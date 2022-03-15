<?php

namespace Ls\Omni\Model\Checkout;

use Laminas\Json\Json;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Block\Stores\Stores;
use \Ls\Omni\Helper\StockHelper;
use \Ls\Omni\Helper\StoreHelper;
use \Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
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
     * @var PageFactory
     */
    public $resultPageFactory;

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
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $storeCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param PageFactory $resultPageFactory
     * @param LSR $lsr
     * @param Session $checkoutSession
     * @param StockHelper $stockHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CollectionFactory $storeCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        PageFactory $resultPageFactory,
        LSR $lsr,
        Session $checkoutSession,
        StockHelper $stockHelper,
        StoreHelper $storeHelper
    ) {
        $this->storeManager           = $storeManager;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->scopeConfig            = $scopeConfig;
        $this->resultPageFactory      = $resultPageFactory;
        $this->lsr                    = $lsr;
        $this->checkoutSession        = $checkoutSession;
        $this->stockHelper            = $stockHelper;
        $this->storeHelper            = $storeHelper;
    }

    /**
     * Get config
     *
     * @return array
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getConfig()
    {

        if ($this->isValid()) {
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
            $resultPage           = $this->resultPageFactory->create();
            $storesData           = $resultPage->getLayout()->createBlock(Stores::class)
                ->setTemplate('Ls_Omni::stores/stores.phtml')
                ->setData('data', $storesResponse)
                ->toHtml();
            $stores               = $storesResponse ? $storesResponse->toArray() : [];
            $stores['storesInfo'] = $storesData;
            $encodedStores        = Json::encode($stores);

            $enabled = $this->lsr->isPickupTimeslotsEnabled();
            if (empty($this->checkoutSession->getStorePickupHours())) {
                $enabled = 0;
            }

            $config                    = [
                'shipping' => [
                    'select_store'          => [
                        'maps_api_key'         => $mapsApiKey,
                        'lat'                  => (float)$defaultLatitude,
                        'lng'                  => (float)$defaultLongitude,
                        'zoom'                 => (int)$defaultZoom,
                        'stores'               => $encodedStores,
                        'available_store_only' => $this->availableStoresOnlyEnabled()
                    ],
                    'pickup_date_timeslots' => [
                        'options'           => $this->checkoutSession->getStorePickupHours(),
                        'enabled'           => $enabled,
                        'current_web_store' => $this->lsr->getActiveWebStore()
                    ]
                ]
            ];
            $config['coupons_display'] = $this->isCouponsDisplayEnabled();

            return $config;
        }

        return [];
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
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getStores()
    {
        $storeHoursArray = [];
        $storesData      = $this->storeCollectionFactory
            ->create()
            ->addFieldToFilter('scope_id', $this->getStoreId())
            ->addFieldToFilter('ClickAndCollect', 1);

        $allStores = $this->storeHelper->getAllStores($this->lsr->getCurrentStoreId());
        foreach ($allStores as $store) {
            if ($store->getIsClickAndCollect() || $store->getIsWebStore()) {
                $storeHoursArray[$store->getId()] = $this->storeHelper->formatDateTimeSlotsValues(
                    $store->getStoreHours()
                );
            }
        }

        if (!empty($storeHoursArray)) {
            $this->checkoutSession->setStorePickupHours($storeHoursArray);
        }

        if (!$this->availableStoresOnlyEnabled()) {
            return $storesData;
        }

        $storeHoursArray = [];

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
     * Available Stores only enabled
     *
     * @return mixed
     */
    public function availableStoresOnlyEnabled()
    {
        return $this->scopeConfig->getValue(self::XPATH_CHECKOUT_ITEM_AVAILABILITY);
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
        foreach ($response as $index => $item) {
            if (!in_array($item->getStoreId(), $clickNCollectStoresIds)) {
                unset($response[$index]);
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
            $itemQty = $item->getQty();
            list($parentProductSku, $childProductSku, , , $uomQty) =
                $this->stockHelper->itemHelper->getComparisonValues($item->getProductId(), $item->getSku());

            if (!empty($uomQty)) {
                $itemQty = $itemQty * $uomQty;
            }

            foreach ($response as $index => $responseItem) {
                if ($responseItem->getItemId() == $parentProductSku &&
                    $responseItem->getVariantId() == $childProductSku &&
                    ceil($responseItem->getQtyInventory()) < $itemQty
                ) {
                    $this->removeAllOccurrenceOfGivenStore($response, $responseItem->getStoreId());
                }
            }
        }

        $responseItems = $this->getAllResponseItemsWithInventoryAvailable($response);

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
            ->addFieldToFilter('scope_id', $this->getStoreId())
            ->addFieldToFilter('nav_id', ['in' => $responseItems]);
    }

    /**
     * Is coupons display enabled
     *
     * @return mixed
     */
    public function isCouponsDisplayEnabled()
    {
        return $this->scopeConfig->getValue(LSR::LS_COUPON_RECOMMENDATIONS_SHOW_ON_CART_CHECKOUT);
    }
}
