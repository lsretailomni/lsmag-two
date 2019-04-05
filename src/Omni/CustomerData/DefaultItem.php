<?php

namespace Ls\Omni\CustomerData;

use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Checkout\CustomerData\AbstractItem;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Core\Model\LSR;

/**
 * Default item
 */
class DefaultItem extends AbstractItem
{
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    public $imageHelper;

    /**
     * @var \Magento\Msrp\Helper\Data
     */
    public $msrpHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $urlBuilder;

    /**
     * @var \Magento\Catalog\Helper\Product\ConfigurationPool
     */
    public $configurationPool;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    public $checkoutHelper;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var ItemResolverInterface
     */
    private $itemResolver;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Msrp\Helper\Data $msrpHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param \Magento\Framework\Escaper|null $escaper
     * @param ItemResolverInterface|null $itemResolver
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Msrp\Helper\Data $msrpHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Framework\Escaper $escaper,
        ItemResolverInterface $itemResolver,
        BasketHelper $basketHelper,
        ItemHelper $itemHelper
    )
    {
        $this->configurationPool = $configurationPool;
        $this->imageHelper = $imageHelper;
        $this->msrpHelper = $msrpHelper;
        $this->urlBuilder = $urlBuilder;
        $this->checkoutHelper = $checkoutHelper;
        $this->escaper = $escaper;
        $this->itemResolver = $itemResolver;
        $this->basketHelper = $basketHelper;
        $this->itemHelper = $itemHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function doGetItemData()
    {
        $imageHelper = $this->imageHelper->init($this->getProductForThumbnail(), 'mini_cart_product_thumbnail');
        $productName = $this->escaper->escapeHtml($this->item->getProduct()->getName());
        $discountPercentageTextMessage = LSR::LS_DISCOUNT_PRICE_PERCENTAGE_TEXT;
        $orginalPrice = '';
        if ($this->item->getCalculationPrice() == $this->item->getCustomPrice()) {
            $orginalPrice = $this->item->getProduct()->getPrice() * $this->item->getQty();
            $percentageDiscount =($this->item->getDiscountPercent()>0 && $this->item->getDiscountPercent()!=null )?
                round($this->item->getDiscountPercent(),2):
                $this->itemHelper->getDiscountPercentage($orginalPrice, $this->item->getCustomPrice());
        } else {
            $orginalPrice = '';
            $percentageDiscount = '';
        }

        return [
            'options' => $this->getOptionList(),
            'qty' => $this->item->getQty() * 1,
            'item_id' => $this->item->getId(),
            'configure_url' => $this->getConfigureUrl(),
            'is_visible_in_site_visibility' => $this->item->getProduct()->isVisibleInSiteVisibility(),
            'product_id' => $this->item->getProduct()->getId(),
            'product_name' => $productName,
            'product_sku' => $this->item->getProduct()->getSku(),
            'product_url' => $this->getProductUrl(),
            'product_has_url' => $this->hasProductUrl(),
            'product_price' => $this->checkoutHelper->formatPrice($this->item->getCalculationPrice()),
            'product_price_value' => $this->item->getCalculationPrice(),
            'product_image' => [
                'src' => $imageHelper->getUrl(),
                'alt' => $imageHelper->getLabel(),
                'width' => $imageHelper->getWidth(),
                'height' => $imageHelper->getHeight(),
            ],
            'canApplyMsrp' => $this->msrpHelper->isShowBeforeOrderConfirm($this->item->getProduct())
                && $this->msrpHelper->isMinimalPriceLessMsrp($this->item->getProduct()),
            'lsPriceOriginal' => ($orginalPrice != "") ?
                $this->checkoutHelper->formatPrice($orginalPrice) : $orginalPrice,
            'lsDiscountPercentage' => ($percentageDiscount != "") ?
                '( '.__($discountPercentageTextMessage)  .' '.$percentageDiscount . '% )':$percentageDiscount
        ];
    }

    /**
     * Get list of all options for product
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getOptionList()
    {
        return $this->configurationPool->getByProductType($this->item->getProductType())->getOptions($this->item);
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProductForThumbnail()
    {
        return $this->itemResolver->getFinalProduct($this->item);
    }

    /**
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProduct()
    {
        return $this->item->getProduct();
    }

    /**
     * Get item configure url
     *
     * @return string
     */
    public function getConfigureUrl()
    {
        return $this->urlBuilder->getUrl(
            'checkout/cart/configure',
            ['id' => $this->item->getId(), 'product_id' => $this->item->getProduct()->getId()]
        );
    }

    /**
     * Check Product has URL
     *
     * @return bool
     */
    public function hasProductUrl()
    {
        if ($this->item->getRedirectUrl()) {
            return true;
        }

        $product = $this->item->getProduct();
        $option = $this->item->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        if ($product->isVisibleInSiteVisibility()) {
            return true;
        } else {
            if ($product->hasUrlDataObject()) {
                $data = $product->getUrlDataObject();
                if (in_array($data->getVisibility(), $product->getVisibleInSiteVisibilities())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retrieve URL to item Product
     *
     * @return string
     */
    public function getProductUrl()
    {
        if ($this->item->getRedirectUrl()) {
            return $this->item->getRedirectUrl();
        }

        $product = $this->item->getProduct();
        $option = $this->item->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        return $product->getUrlModel()->getUrl($product);
    }
}
