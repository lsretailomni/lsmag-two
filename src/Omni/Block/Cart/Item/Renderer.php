<?php

namespace Ls\Omni\Block\Cart\Item;

use Exception;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use Magento\Bundle\Helper\Catalog\Product\Configuration;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Framework\View\Element\Template\Context;

/**
 * Overriding the default item renderer
 *
 */
class Renderer extends \Magento\Checkout\Block\Cart\Item\Renderer
{
    /**
     * Bundle catalog product configuration
     *
     * @var Configuration
     */
    protected $_bundleProductConfiguration = null;

    /**
     * @param Context $context
     * @param \Magento\Catalog\Helper\Product\Configuration $productConfig
     * @param Session $checkoutSession
     * @param ImageBuilder $imageBuilder
     * @param Data $urlHelper
     * @param ManagerInterface $messageManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param Manager $moduleManager
     * @param InterpretationStrategyInterface $messageInterpretationStrategy
     * @param Configuration $_bundleProductConfiguration
     * @param array $data
     * @param ItemResolverInterface|null $itemResolver
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Module\Manager $moduleManager,
        InterpretationStrategyInterface $messageInterpretationStrategy,
        Configuration $_bundleProductConfiguration,
        array $data = [],
        ItemResolverInterface $itemResolver = null
    ) {
        $this->_bundleProductConfiguration = $_bundleProductConfiguration;
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
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @param $item
     * @return array|null
     */
    public function getOneListCalculateData($item)
    {
        try {
            $this->basketHelper = $this->getBasketHelper();
            $this->itemHelper   = $this->getBasketHelper()->getItemHelper();
            if ($item->getPrice() <= 0) {
                $this->basketHelper->cart->save();
            }
            $basketData = $this->basketHelper->getBasketSessionValue();
            $result     = $this->itemHelper->getOrderDiscountLinesForItem($item, $basketData);
            return $result;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @return BasketHelper
     */
    private function getBasketHelper()
    {
        return ObjectManager::getInstance()
            ->get('\Ls\Omni\Helper\BasketHelper');
    }

    /**
     * @return PriceCurrencyInterface
     */

    public function getPriceCurrency()
    {
        return $this->priceCurrency;
    }

    /**
     * @return array
     */
    public function getOptionList()
    {
        if ($this->getItem()->getProductType() == Type::TYPE_BUNDLE) {
            return $this->_bundleProductConfiguration->getOptions($this->getItem());
        }
        return $this->_productConfig->getOptions(parent::getItem());
    }

    /**
     * @param $item
     * @return string
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function getItemRowTotal($item)
    {
        $basketHelper = $this->getBasketHelper();
        return $basketHelper->getItemRowTotal($item);
    }
}
