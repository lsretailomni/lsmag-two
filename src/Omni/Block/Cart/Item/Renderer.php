<?php

namespace Ls\Omni\Block\Cart\Item;

use Magento\Catalog\Pricing\Price\ConfiguredPriceInterface;
use Magento\Checkout\Block\Cart\Item\Renderer\Actions;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Core\Model\LSR;
use \Ls\omni\Helper\ItemHelper;

/**
 * Class Renderer
 * @package Ls\Omni\Block\Cart\Item
 */
class Renderer extends \Magento\Framework\View\Element\Template implements
    \Magento\Framework\DataObject\IdentityInterface
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

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Helper\Product\Configuration $productConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param InterpretationStrategyInterface $messageInterpretationStrategy
     * @param array $data
     * @param ItemResolverInterface|null $itemResolver
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @codeCoverageIgnore
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
        $this->priceCurrency = $priceCurrency;
        $this->imageBuilder = $imageBuilder;
        $this->urlHelper = $urlHelper;
        $this->productConfig = $productConfig;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->basketHelper = $basketHelper;
        $this->itemHelper = $itemHelper;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
        $this->moduleManager = $moduleManager;
        $this->messageInterpretationStrategy = $messageInterpretationStrategy;
        $this->itemResolver = $itemResolver;
    }

    /**
     * @param AbstractItem $item
     * @return $this
     */
    public function setItem(AbstractItem $item)
    {
        $this->_item = $item;

        return $this;
    }

    /**
     * Get quote item
     *
     * @return AbstractItem
     * @codeCoverageIgnore
     */
    public function getItem()
    {
        return $this->_item;
    }

    /**
     * Get item product
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProduct()
    {
        return $this->getItem()->getProduct();
    }

    /**
     * Identify the product from which thumbnail should be taken.
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProductForThumbnail()
    {
        return $this->itemResolver->getFinalProduct($this->getItem());
    }

    /**
     * @param string $productUrl
     * @return $this
     * @codeCoverageIgnore
     */
    public function overrideProductUrl($productUrl)
    {
        $this->_productUrl = $productUrl;

        return $this;
    }

    /**
     * Check Product has URL
     *
     * @return bool
     */
    public function hasProductUrl()
    {
        if ($this->ignoreProductUrl) {
            return false;
        }

        if ($this->productUrl || $this->getItem()->getRedirectUrl()) {
            return true;
        }

        $product = $this->getProduct();
        $option = $this->getItem()->getOptionByCode('product_type');
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
        if ($this->productUrl !== null) {
            return $this->productUrl;
        }
        if ($this->getItem()->getRedirectUrl()) {
            return $this->getItem()->getRedirectUrl();
        }

        $product = $this->getProduct();
        $option = $this->getItem()->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        return $product->getUrlModel()->getUrl($product);
    }

    /**
     * Get item product name
     *
     * @return string
     */
    public function getProductName()
    {
        if ($this->hasProductName()) {
            return $this->getData('product_name');
        }

        return $this->getProduct()->getName();
    }

    /**
     * Get product customize options
     *
     * @return array
     */
    public function getProductOptions()
    {
        /* @var $helper \Magento\Catalog\Helper\Product\Configuration */
        $helper = $this->productConfig;

        return $helper->getCustomOptions($this->getItem());
    }

    /**
     * Get list of all options for product
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getOptionList()
    {
        return $this->productConfig->getOptions($this->getItem());
    }

    /**
     * Get quote item qty
     *
     * @return float|int
     */
    public function getQty()
    {
        if (!$this->strictQtyMode && (string)$this->getItem()->getQty() == '') {
            return '';
        }

        return $this->getItem()->getQty() * 1;
    }

    /**
     * Get checkout session
     *
     * @return \Magento\Checkout\Model\Session
     * @codeCoverageIgnore
     */
    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * Retrieve item messages
     * Return array with keys
     *
     * text => the message text
     * type => type of a message
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = [];
        $quoteItem = $this->getItem();

        // Add basic messages occurring during this page load
        $baseMessages = $quoteItem->getMessage(false);
        if ($baseMessages) {
            foreach ($baseMessages as $message) {
                $messages[] = ['text' => $message, 'type' => $quoteItem->getHasError() ? 'error' : 'notice'];
            }
        }

        /* @var $collection \Magento\Framework\Message\Collection */
        $collection = $this->messageManager->getMessages(true, 'quote_item' . $quoteItem->getId());
        if ($collection) {
            $additionalMessages = $collection->getItems();
            foreach ($additionalMessages as $message) {
                /* @var $message \Magento\Framework\Message\MessageInterface */
                $messages[] = [
                    'text' => $this->messageInterpretationStrategy->interpret($message),
                    'type' => $message->getType(),
                ];
            }
        }
        $this->messageManager->getMessages(true, 'quote_item' . $quoteItem->getId())->clear();

        return $messages;
    }

    /**
     * Accept option value and return its formatted view
     *
     * @param string|array $optionValue
     * Method works well with these $optionValue format:
     *      1. String
     *      2. Indexed array e.g. array(val1, val2, ...)
     *      3. Associative array, containing additional option info, including option value, e.g.
     *          array
     *          (
     *              [label] => ...,
     *              [value] => ...,
     *              [print_value] => ...,
     *              [option_id] => ...,
     *              [option_type] => ...,
     *              [custom_view] =>...,
     *          )
     *
     * @return array
     */
    public function getFormatedOptionValue($optionValue)
    {
        /* @var $helper \Magento\Catalog\Helper\Product\Configuration */
        $helper = $this->productConfig;
        $params = [
            'max_length' => 55,
            'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>',
        ];

        return $helper->getFormattedOptionValue($optionValue, $params);
    }

    /**
     * Check whether Product is visible in site
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isProductVisible()
    {
        return $this->getProduct()->isVisibleInSiteVisibility();
    }

    /**
     * Return product additional information block
     *
     * @return AbstractBlock
     * @codeCoverageIgnore
     */
    public function getProductAdditionalInformationBlock()
    {
        return $this->getLayout()->getBlock('additional.product.info');
    }

    /**
     * Set qty mode to be strict or not
     *
     * @param bool $strict
     * @return $this
     * @codeCoverageIgnore
     */
    public function setQtyMode($strict)
    {
        $this->strictQtyMode = $strict;

        return $this;
    }

    /**
     * Set ignore product URL rendering
     *
     * @param bool $ignore
     * @return $this
     * @codeCoverageIgnore
     */
    public function setIgnoreProductUrl($ignore = true)
    {
        $this->ignoreProductUrl = $ignore;

        return $this;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
        if ($this->getItem()) {
            $identities = $this->getProduct()->getIdentities();
        }

        return $identities;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductPriceHtml(\Magento\Catalog\Model\Product $product)
    {
        $priceRender = $this->getPriceRender();
        $priceRender->setItem($this->getItem());

        $price = '';
        if ($priceRender) {
            $price = $priceRender->render(
                ConfiguredPriceInterface::CONFIGURED_PRICE_CODE,
                $product,
                [
                    'include_container' => true,
                    'display_minimal_price' => true,
                    'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST,
                ]
            );
        }

        return $price;
    }

    /**
     * @return bool|\Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getPriceRender()
    {
        return $this->getLayout()->getBlock('product.price.render.default');
    }

    /**
     * Convert prices for template
     *
     * @param float $amount
     * @param bool $format
     * @return float
     */
    public function convertPrice($amount, $format = false)
    {
        return $format
            ? $this->priceCurrency->convertAndFormat($amount)
            : $this->priceCurrency->convert($amount);
    }

    /**
     * @param AbstractItem $item
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getUnitPriceHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.item.price.unit');
        $block->setItem($item);

        return $block->toHtml();
    }

    /**
     * @param AbstractItem $item
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRowTotalHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.item.price.row');
        $block->setItem($item);

        return $block->toHtml();
    }

    /**
     * @param AbstractItem $item
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSidebarItemPriceHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.cart.item.price.sidebar');
        $block->setItem($item);

        return $block->toHtml();
    }

    /**
     * @param AbstractItem $item
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getUnitPriceExclTaxHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.onepage.review.item.price.unit.excl');
        $block->setItem($item);

        return $block->toHtml();
    }

    /**
     * @param AbstractItem $item
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getUnitPriceInclTaxHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.onepage.review.item.price.unit.incl');
        $block->setItem($item);

        return $block->toHtml();
    }

    /**
     * @param AbstractItem $item
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRowTotalExclTaxHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.onepage.review.item.price.rowtotal.excl');
        $block->setItem($item);

        return $block->toHtml();
    }

    /**
     * @param AbstractItem $item
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRowTotalInclTaxHtml(AbstractItem $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('checkout.onepage.review.item.price.rowtotal.incl');
        $block->setItem($item);

        return $block->toHtml();
    }

    /**
     * Get row total including tax html
     *
     * @param AbstractItem $item
     * @return string
     */
    public function getActions(AbstractItem $item)
    {
        /** @var Actions $block */
        $block = $this->getChildBlock('actions');
        if ($block instanceof Actions) {
            $block->setItem($item);

            return $block->toHtml();
        } else {
            return '';
        }
    }

    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
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
        $basketData = $this->basketHelper->getBasketSessionValue();
        $discountText = LSR::LS_DISCOUNT_PRICE_PERCENTAGE_TEXT;
        foreach ($basketData->getOrderLines() as $basket) {
            if ($basket->getItemId() == $itemSku[0]) {
                if ($basket->getDiscountAmount() != "0.00") {
                    if (is_array($basketData->getOrderDiscountLines()->getOrderDiscountLine())) {
                        $discountInfo = $basketData->getOrderDiscountLines()->getOrderDiscountLine()[$counter];
                    } else {
                        $discountInfo = $basketData->getOrderDiscountLines()->getOrderDiscountLine();
                    }
                    $counter++;
                    return [$basket, $discountInfo->getDescription(),$discountText];
                }
            }
        }
    }

}