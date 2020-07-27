<?php

namespace Ls\Omni\Block\Product\View;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfRecommendedItem;
use \Ls\Omni\Helper\LSRecommend as LSRecommendHelper;
use Magento\Catalog\Api\Data\ProductInterface;
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
 * Class Recommend
 * @package Ls\Omni\Block\Product\View
 */
class Recommend extends \Magento\Catalog\Block\Product\View
{
    /** @var LSR */
    public $lsr;

    /** @var LSRecommendHelper */
    public $LSRecommend;

    /**
     * Recommend constructor.
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
     * @param LSRecommendHelper $LS_RecommendHelper
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
        LSRecommendHelper $LS_RecommendHelper,
        array $data = []
    ) {
        $this->lsr         = $lsr;
        $this->LSRecommend = $LS_RecommendHelper;
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
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('omni/ajax/recommendation');
    }

    /**
     * @return string|null
     */
    public function getProductBySku()
    {
        $currentProduct = $this->getProduct();
        if (empty($currentProduct) || !$currentProduct->getId()) {
            return null;
        }
        return $currentProduct->getSku();
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->LSRecommend->isLsRecommendEnable() && $this->LSRecommend->isLsRecommendEnableOnProductPage()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $productId
     * @return ProductInterface[]|null
     */
    public function getProductRecommendation($productId)
    {
        $response = null;
        if (empty($productId)) {
            return $response;
        }
        $recommendedProducts = $this->LSRecommend->getProductRecommendationFromOmni($productId);
        if ($recommendedProducts instanceof ArrayOfRecommendedItem) {
            return $this->LSRecommend->parseProductRecommendation($recommendedProducts);
        }
        return $response;
    }
}
