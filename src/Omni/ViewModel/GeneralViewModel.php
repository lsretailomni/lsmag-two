<?php
declare(strict_types=1);

namespace Ls\Omni\ViewModel;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class GeneralViewModel implements ArgumentInterface
{
    /**
     * @var null
     */
    public $product = null;

    /**
     * @param LSR $lsr
     * @param Data $catalogHelper
     */
    public function __construct(
        public LSR $lsr,
        public Data $catalogHelper
    ) {
    }

    /**
     * Get default google map api key from config
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getGoogleMapsApiKey(): ?string
    {
        return $this->lsr->getGoogleMapsApiKey();
    }

    /**
     * Get default latitude from config
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultLatitude(): string
    {
        return $this->lsr->getDefaultLatitude();
    }

    /**
     * Get default longitude from config
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultLongitude(): string
    {
        return $this->lsr->getDefaultLongitude();
    }

    /**
     * Get default default zoom from config
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getDefaultZoom(): string
    {
        return $this->lsr->getDefaultZoom();
    }

    /**
     * Check to see if push notification is enabled
     *
     * @return bool
     */
    public function isPushNotificationsEnabled(): bool
    {
        return $this->lsr->isPushNotificationsEnabled();
    }

    /**
     * Check if commerce is responding
     *
     * @return bool|null
     * @throws NoSuchEntityException|GuzzleException
     */
    public function isValid(): ?bool
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
    public function getProduct(): ?Product
    {
        if ($this->product === null) {
            $this->product = $this->catalogHelper->getProduct();
        }

        return $this->product;
    }
}
