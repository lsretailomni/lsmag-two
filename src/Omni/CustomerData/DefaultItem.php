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
class DefaultItem extends \Magento\Checkout\CustomerData\DefaultItem
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
        $this->basketHelper = $basketHelper;
        $this->itemHelper = $itemHelper;
        parent::__construct(
            $imageHelper,
            $msrpHelper,
            $urlBuilder,
            $configurationPool,
            $checkoutHelper,
            $escaper,
            $itemResolver
        );
    }

    /**
     * {@inheritdoc}
     */
    public function doGetItemData()
    {
        $discountPercentageTextMessage = LSR::LS_DISCOUNT_PRICE_PERCENTAGE_TEXT;
        $orginalPrice = '';
        if ($this->item->getCalculationPrice() == $this->item->getCustomPrice()) {
            $orginalPrice = $this->item->getProduct()->getPrice() * $this->item->getQty();
            $percentageDiscount = ($this->item->getDiscountPercent() > 0 && $this->item->getDiscountPercent() != null) ?
                round($this->item->getDiscountPercent(), 2) :
                $this->itemHelper->getDiscountPercentage($orginalPrice, $this->item->getCustomPrice());
        } else {
            $orginalPrice = '';
            $percentageDiscount = '';
        }

        $itemsData = parent::doGetItemData($this->item);
        return \array_merge(
            ['lsPriceOriginal' => ($orginalPrice != "") ?
                $this->checkoutHelper->formatPrice($orginalPrice) : $orginalPrice,
                'lsDiscountPercentage' => ($percentageDiscount != "") ?
                    '( ' . __($discountPercentageTextMessage) . ' ' . $percentageDiscount . '% )' : $percentageDiscount
            ],
            $itemsData
        );
    }
}
