<?php

namespace Ls\Omni\Block\Cart\Item;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Core\Model\LSR;
use \Ls\omni\Helper\ItemHelper;

/**
 * Class Renderer
 * @package Ls\Omni\Block\Cart\Item
 */
class Renderer extends \Magento\Checkout\Block\Cart\Item\Renderer
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;

    /**
     * @var AbstractItem
     */
    public $item;

    /**
     * @var string
     */
    public $productUrl;

    /**
     * Whether qty will be converted to number
     *
     * @var bool
     */
    public $strictQtyMode = true;

    /**
     * Check, whether product URL rendering should be ignored
     *
     * @var bool
     */
    public $ignoreProductUrl = false;

    /**
     * Catalog product configuration
     *
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    public $productConfig = null;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    public $urlHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    public $messageManager;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    public $imageBuilder;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    public $moduleManager;

    /**
     * @var InterpretationStrategyInterface
     */
    private $messageInterpretationStrategy;

    /**
     * @var ItemResolverInterface
     */
    private $itemResolver;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * ItemHelper
     */
    public $itemHelper;

    /** counter for count values */
    public $counter = 0;

    /**
     * Renderer constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Helper\Product\Configuration $productConfig
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param InterpretationStrategyInterface $messageInterpretationStrategy
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param array $data
     * @param ItemResolverInterface $itemResolver
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Module\Manager $moduleManager,
        InterpretationStrategyInterface $messageInterpretationStrategy,
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        array $data = [],
        ItemResolverInterface $itemResolver
    )
    {
        $this->basketHelper = $basketHelper;
        $this->itemHelper = $itemHelper;
        $this->productConfig = $productConfig;
        parent::__construct($context,
            $productConfig,
            $checkoutSession,
            $imageBuilder,
            $urlHelper,
            $messageManager,
            $priceCurrency,
            $moduleManager,
            $messageInterpretationStrategy,
            $data,
            $itemResolver
        );
    }

    /**
     * @param $item
     * @return array
     */
    public function getOneListCalculateData($item)
    {
        $counter = 0;
        $itemSku = $item->getSku();
        $itemSku = explode("-", $itemSku);
        if (count($itemSku) < 2) {
            $itemSku[1] = null;
        }
        $basketData = $this->basketHelper->getBasketSessionValue();
        $discountText = LSR::LS_DISCOUNT_PRICE_PERCENTAGE_TEXT;

        foreach ($basketData->getOrderLines() as $basket) {
            if ($basket->getItemId() == $itemSku[0] && $basket->getVariantId() == $itemSku[1]) {
                if ($item->getCustomPrice() != "0.00" && $item->getCustomPrice() != null) {
                    if (is_array($basketData->getOrderDiscountLines()->getOrderDiscountLine())) {
                        //TODO Add discount Line Description for multiple offers applied on the cart.
                        $discountInfo = '';
                    } else {
                        $discountInfo = $basketData->getOrderDiscountLines()->getOrderDiscountLine()->getDescription();
                    }
                    return [$basket, $discountInfo, $discountText];
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getOptionList()
    {
        return $this->productConfig->getOptions(parent::getItem());
    }

}