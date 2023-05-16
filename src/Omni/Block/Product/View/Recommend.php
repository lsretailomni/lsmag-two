<?php

namespace Ls\Omni\Block\Product\View;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfRecommendedItem;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\LSRecommend as LSRecommendHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;

class Recommend extends \Magento\Catalog\Block\Product\View
{
    /** @var LSR */
    public $lsr;

    /** @var LSRecommendHelper */
    public $LSRecommend;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
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
     * @param ItemHelper $itemHelper
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
        ItemHelper $itemHelper,
        array $data = []
    ) {
        $this->lsr         = $lsr;
        $this->LSRecommend = $LS_RecommendHelper;
        $this->itemHelper  = $itemHelper;
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
     * @throws NoSuchEntityException
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
     * Get recommendation given itemId
     *
     * @param string $itemId
     * @return ProductInterface[]|null
     * @throws NoSuchEntityException
     */
    public function getProductRecommendation($itemId)
    {
        if (empty($itemId)) {
            return null;
        }
        $recommendedProducts = $this->LSRecommend->getProductRecommendationFromOmni($itemId);
        if ($recommendedProducts instanceof ArrayOfRecommendedItem) {
            return $this->LSRecommend->parseProductRecommendation($recommendedProducts);
        }
        return null;
    }

    /**
     * @return bool|null
     * @throws NoSuchEntityException
     */
    public function isValid()
    {
        return $this->lsr->isLSR($this->lsr->getCurrentStoreId());
    }

    /**
     * Get Ls Central Item Id by sku
     *
     * @param string $sku
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getLsCentralItemIdBySku($sku)
    {
        return $this->itemHelper->getLsCentralItemIdBySku($sku);
    }

    /**
     * Get product given sku
     *
     * @param $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProductGivenSku($sku)
    {
        return $this->itemHelper->getProductGivenSku($sku);
    }

    /**
     * Get bundle product linked item_ids
     *
     * @param $bundleProduct
     * @return array
     * @throws NoSuchEntityException
     * @throws InputException
     */
    public function getLinkedProductsItemIds($bundleProduct)
    {
        return $this->itemHelper->getLinkedProductsItemIds($bundleProduct);
    }
}
