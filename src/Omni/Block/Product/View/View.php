<?php

namespace Ls\Omni\Block\Product\View;

use \Ls\Core\Model\LSR;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;

/**
 * Class View
 * @package Ls\Omni\Block\Product\View
 */
class View extends \Magento\Catalog\Block\Product\View
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * View constructor.
     * @param LSR $lsr
     * @param Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param EncoderInterface $jsonEncoder
     * @param StringUtils $string
     * @param Product $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Proxy $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        LSR $lsr,
        Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        EncoderInterface $jsonEncoder,
        StringUtils $string,
        Product $productHelper,
        ConfigInterface $productTypeConfig,
        FormatInterface $localeFormat,
        Proxy $customerSession,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->lsr = $lsr;
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
    }

    /**
     * Get default google map api key from config
     * @return string
     */
    public function getGoogleMapsApiKey()
    {
        $configValue = $this->lsr->getGoogleMapsApiKey();
        return $configValue;
    }

    /**
     * Get default latitude from config
     * @return string
     */
    public function getDefaultLatitude()
    {
        $configValue = $this->lsr->getDefaultLatitude();
        return $configValue;
    }

    /**
     * Get default longitude from config
     * @return string
     */
    public function getDefaultLongitude()
    {
        $configValue = $this->lsr->getDefaultLongitude();
        return $configValue;
    }

    /**
     * Get default default zoom from config
     * @return string
     */
    public function getDefaultZoom()
    {
        $configValue = $this->lsr->getDefaultZoom();
        return $configValue;
    }

    /**
     * Get default default zoom from config
     * @return string
     */
    public function isEnable()
    {
        $configValue = $this->lsr->getStoreConfig(
            $this->lsr::SC_CART_PRODUCT_AVAILABILITY,
            $this->lsr->getCurrentStoreId()
        );
        return $configValue;
    }
}
