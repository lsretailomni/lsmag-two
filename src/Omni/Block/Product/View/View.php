<?php


namespace Ls\Omni\Block\Product\View;

use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Class View
 * @package Ls\Omni\Block\Product\View
 */

class View extends \Magento\Catalog\Block\Product\View
{
    /**
     * @var \Ls\Core\Model\LSR
     */
    protected $_lsr;

    /**
     * View constructor.
     * @param \Ls\Core\Model\LSR $lsr
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        \Ls\Core\Model\LSR $lsr,
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->_lsr = $lsr;
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
        $configValue = $this->_lsr->getGoogleMapsApiKey();
        return $configValue;
    }

    /**
     * Get default latitude from config
     * @return string
     */
    public function getDefaultLatitude()
    {
        $configValue = $this->_lsr->getDefaultLatitude();
        return $configValue;
    }

    /**
     * Get default longitude from config
     * @return string
     */
    public function getDefaultLongitude()
    {
        $configValue = $this->_lsr->getDefaultLongitude();
        return $configValue;
    }

    /**
     * Get default default zoom from config
     * @return string
     */
    public function getDefaultZoom()
    {
        $configValue = $this->_lsr->getDefaultZoom();
        return $configValue;
    }

    /**
     * Get default default zoom from config
     * @return string
     */
    public function isEnable()
    {
        $configValue = $this->_lsr->getStoreConfig($this->_lsr::SC_CART_PRODUCT_AVAILABILITY);
        return $configValue;
    }
}
