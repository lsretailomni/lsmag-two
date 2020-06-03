<?php

namespace Ls\Omni\Model\Checkout;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class DataProvider
 * @package Ls\Omni\Model\Checkout
 */
class DataProvider implements ConfigProviderInterface
{
    const XPATH_MAPS_API_KEY = 'omni_clickandcollect/general/maps_api_key';
    const XPATH_DEFAULT_LATITUDE = 'omni_clickandcollect/general/default_latitude';
    const XPATH_DEFAULT_LONGITUDE = 'omni_clickandcollect/general/default_longitude';
    const XPATH_DEFAULT_ZOOM = 'omni_clickandcollect/general/default_zoom';

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var CollectionFactory */
    public $storeCollectionFactory;

    /** @var ScopeConfigInterface */
    public $scopeConfig;

    /** @var GiftCardHelper */
    public $giftCardHelper;

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * DataProvider constructor.
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $storeCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param GiftCardHelper $giftCardHelper
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CollectionFactory $storeCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        GiftCardHelper $giftCardHelper,
        PageFactory $resultPageFactory
    ) {
        $this->storeManager           = $storeManager;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->scopeConfig            = $scopeConfig;
        $this->giftCardHelper         = $giftCardHelper;
        $this->resultPageFactory      = $resultPageFactory;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        $store            = $this->getStoreId();
        $mapsApiKey       = $this->scopeConfig->getValue(self::XPATH_MAPS_API_KEY, ScopeInterface::SCOPE_STORE, $store);
        $defaultLatitude  = $this->scopeConfig->getValue(
            self::XPATH_DEFAULT_LATITUDE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $defaultLongitude = $this->scopeConfig->getValue(
            self::XPATH_DEFAULT_LONGITUDE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $defaultZoom      = $this->scopeConfig->getValue(self::XPATH_DEFAULT_ZOOM, ScopeInterface::SCOPE_STORE, $store);

        $storesResponse = $this->getStores();

        $resultPage = $this->resultPageFactory->create();
        $storesData = $resultPage->getLayout()->createBlock('Ls\Omni\Block\Stores\Stores')
            ->setTemplate('Ls_Omni::stores/stores.phtml')
            ->setData('data', $storesResponse)
            ->toHtml();

        $stores               = $storesResponse->toArray();
        $stores['storesInfo'] = $storesData;
        $encodedStores        = \Zend_Json::encode($stores);

        $config                     = [
            'shipping' => [
                'select_store' => [
                    'maps_api_key' => $mapsApiKey,
                    'lat'          => (float)$defaultLatitude,
                    'lng'          => (float)$defaultLongitude,
                    'zoom'         => (int)$defaultZoom,
                    'stores'       => $encodedStores
                ]
            ]
        ];
        $config['gift_card_enable'] = $this->giftCardHelper->isGiftCardEnableOnCheckOut();
        $config['coupons_display']  = $this->isCouponsDisplayEnabled();
        return $config;
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getStoreId();
    }

    public function getStores()
    {
        $stores = $this->storeCollectionFactory
            ->create()
            ->addFieldToFilter('scope_id', $this->getStoreId())
            ->addFieldToFilter('ClickAndCollect', 1);

        return $stores;
    }

    /**
     * @return mixed
     */
    public function isCouponsDisplayEnabled()
    {
        return $this->scopeConfig->getValue(LSR::LS_COUPON_SHOW_ON_CART_CHECKOUT);
    }
}
