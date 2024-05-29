<?php

namespace Ls\Omni\ViewModel;

use Ls\Core\Model\LSR;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class GeneralViewModel implements ArgumentInterface
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Data
     */
    public $catalogHelper;

    /**
     * @var null
     */
    public $product = null;

    /**
     * @param LSR $lsr
     * @param Data $catalogHelper
     */
    public function __construct(
        LSR $lsr,
        Data $catalogHelper
    ) {
        $this->lsr = $lsr;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * Get default google map api key from config
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getGoogleMapsApiKey()
    {
        return $this->lsr->getGoogleMapsApiKey();
    }

    /**
     * Get default latitude from config
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultLatitude()
    {
        return $this->lsr->getDefaultLatitude();
    }

    /**
     * Get default longitude from config
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultLongitude()
    {
        return $this->lsr->getDefaultLongitude();
    }

    /**
     * Get default default zoom from config
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultZoom()
    {
        return $this->lsr->getDefaultZoom();
    }

    /**
     * Get configured app_id
     *
     * @return mixed
     */
    public function getAppId()
    {
        return $this->lsr->getAppId();
    }

    /**
     * Get configured rest_api_key
     *
     * @return mixed
     */
    public function getRestApiKey()
    {
        return $this->lsr->getRestApiKey();
    }

    /**
     * Get configuration for notification type
     *
     * @return string
     */
    public function getNotificationType()
    {
        return $this->lsr->getNotificationType();
    }

    /**
     * Check to see if push notification is enabled
     *
     * @return bool
     */
    public function isPushNotificationsEnabled()
    {
        $configuredNotificationType = explode(',', $this->getNotificationType());

        return in_array(LSR::LS_NOTIFICATION_PUSH_NOTIFICATION, $configuredNotificationType);
    }

    /**
     * Check if commerce is responding
     *
     * @return bool|null
     * @throws NoSuchEntityException
     */
    public function isValid()
    {
        return $this->lsr->isLSR($this->lsr->getCurrentStoreId()) &&
            !in_array(
                $this->getProduct()->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE),
                explode(',', $this->lsr->getGiftCardIdentifiers())
            );
    }

    /**
     * Get current product
     *
     * @return Product|null
     */
    public function getProduct()
    {
        if ($this->product === null) {
            $this->product = $this->catalogHelper->getProduct();
        }

        return $this->product;
    }
}
