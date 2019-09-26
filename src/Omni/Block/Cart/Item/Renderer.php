<?php

namespace Ls\Omni\Block\Cart\Item;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\omni\Helper\ItemHelper;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;

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
        ItemResolverInterface $itemResolver,
        array $data = []
    ) {
        $this->basketHelper = $basketHelper;
        $this->itemHelper = $itemHelper;
        $this->productConfig = $productConfig;
        parent::__construct(
            $context,
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
     * @return array|null
     */
    public function getOneListCalculateData($item)
    {
        try {
            if ($item->getPrice() <= 0) {
                $this->basketHelper->cart->save();
            }
            $basketData = $this->basketHelper->getBasketSessionValue();
            $result = $this->itemHelper->getOrderDiscountLinesForItem($item, $basketData);
            return $result;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
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
