<?php


namespace Ls\Omni\Block\Product\View;

use Magento\Catalog\Api\ProductRepositoryInterface;
use \Ls\Omni\Helper\LSRecommend as LSRecommendHelper;

/**
 * Class View
 * @package Ls\Omni\Block\Product\View
 */

class Recommend extends \Magento\Catalog\Block\Product\View
{
    /** @var \Ls\Core\Model\LSR  */
    public $lsr;

    /** @var LSRecommendHelper  */
    public $LSRecommend;

    /**
     * Recommend constructor.
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
     * @param LSRecommendHelper $LS_RecommendHelper
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
        LSRecommendHelper $LS_RecommendHelper,
        array $data = []
    ) {
        $this->lsr = $lsr;
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
     * @return \Magento\Catalog\Api\Data\ProductInterface[]|null
     */
    public function getProductRecommendation()
    {
        // only process if LS Recommend is enabled in general and on product page.

        $response = null;

        if (!$this->LSRecommend->isLsRecommendEnable() || !$this->LSRecommend->isLsRecommendEnableOnProductPage()) {
            return $response;
        }
        $currentProduct = $this->getProduct();
        if (!$currentProduct->getId()) {
            // not on product page.

            return $response;
        }

        $recommendedProducts = $this->LSRecommend->getProductRecommendationfromOmni($currentProduct->getSku());

        // this is the recommended products we received from NAV so now we need to get the actual products from that.

        // check to see if we get correct response not full of errors.

        if ($recommendedProducts instanceof \Ls\Omni\Client\Ecommerce\Entity\ArrayOfRecommendedItem) {
            return $this->LSRecommend->parseProductRecommendation($recommendedProducts);
        }
        return $response;
    }


}
