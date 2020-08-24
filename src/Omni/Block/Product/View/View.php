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
use Magento\Framework\Exception\NoSuchEntityException;

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
        return $this->lsr->getGoogleMapsApiKey();
    }

    /**
     * Get default latitude from config
     * @return string
     */
    public function getDefaultLatitude()
    {
        return $this->lsr->getDefaultLatitude();
    }

    /**
     * Get default longitude from config
     * @return string
     */
    public function getDefaultLongitude()
    {
        return $this->lsr->getDefaultLongitude();
    }

    /**
     * Get default default zoom from config
     * @return string
     */
    public function getDefaultZoom()
    {
        return $this->lsr->getDefaultZoom();
    }

    /**
     * @return bool|null
     * @throws NoSuchEntityException
     */
    public function isValid()
    {
        return  $this->lsr->isLSR($this->lsr->getCurrentStoreId());
    }
}
