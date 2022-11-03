<?php

namespace Ls\Customer\Block\Order\Item\Custom;

use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;

class Renderer extends DefaultRenderer
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/item/custom/renderer.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @var Collection|null
     */
    private $itemCollection;

    /**
     * @var CollectionFactory|mixed|null
     */
    public $itemCollectionFactory;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @param Context $context
     * @param StringUtils $string
     * @param OptionFactory $productOptionFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param ItemHelper $itemHelper
     * @param CollectionFactory $itemCollectionFactory
     * @param OrderHelper $orderHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        StringUtils $string,
        OptionFactory $productOptionFactory,
        PriceCurrencyInterface $priceCurrency,
        ItemHelper $itemHelper,
        CollectionFactory $itemCollectionFactory,
        OrderHelper $orderHelper,
        array $data = []
    ) {
        $this->priceCurrency         = $priceCurrency;
        $this->itemHelper            = $itemHelper;
        $this->orderHelper           = $orderHelper;
        $this->itemCollectionFactory = $itemCollectionFactory;
        parent::__construct($context, $string, $productOptionFactory, $data);
    }

    /**
     * Return sku of order item.
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getItem()->getItemId();
    }

    /**
     * Get Item Options
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getItemOptions()
    {
        $result    = [];
        $orderItem = $this->getOrderItem();

        if ($orderItem) {
            $options = $orderItem->getProductOptions();

            if ($options) {
                if (isset($options['options'])) {
                    $result[] = $options['options'];
                }
                if (isset($options['additional_options'])) {
                    $result[] = $options['additional_options'];
                }
                if (isset($options['attributes_info'])) {
                    $result[] = $options['attributes_info'];
                }
            }
        }

        return array_merge([], ...$result);
    }

    /**
     * Get respective order item from magento
     *
     * @return DataObject|null
     * @throws NoSuchEntityException
     */
    public function getOrderItem()
    {
        $magentoOrderItem = null;
        $centralItem      = $this->getItem();
        $magentoOrder     = $this->orderHelper->getGivenValueFromRegistry('current_mag_order');

        if ($magentoOrder) {
            $this->itemCollection = $this->itemCollectionFactory->create();
            $this->itemCollection->setOrderFilter($magentoOrder);
            foreach ($this->itemCollection->getItems() as $orderItem) {
                if (!$orderItem->getParentItemId()) {
                    list($itemId, $variantId, $uom) = $this->itemHelper->getComparisonValues(
                        $orderItem->getProductId(),
                        $orderItem->getSku()
                    );
                    if ($itemId == $centralItem->getItemId() &&
                        $variantId == $centralItem->getVariantId() &&
                        $uom == $centralItem->getUomId()
                    ) {
                        $magentoOrderItem = $orderItem;
                        break;
                    }
                }
            }
        }

        return $magentoOrderItem;
    }

    /**
     * Get Item
     *
     * @return array|null
     */
    public function getItem()
    {
        return $this->getData('item');
    }

    /**
     * Get Formatted qty
     *
     * @param $qty
     * @return string
     */
    public function getFormattedQty($qty)
    {
        return number_format((float)$qty, 2, '.', '');
    }

    /**
     * Get formatted price
     *
     * @param $amount
     * @param $currency
     * @param $storeId
     * @return float
     */
    public function getFormattedPrice($amount, $currency = null, $storeId = null)
    {
        return $this->orderHelper->getPriceWithCurrency($this->priceCurrency, $amount, $currency, $storeId);
    }

    /**
     * Get Item Discount Lines
     *
     * @return array|null
     */
    public function getItemDiscountLines()
    {
        $item      = $this->getItem();
        $orderData = $this->getData('order');

        return $this->itemHelper->getOrderDiscountLinesForItem($item, $orderData, 2);
    }

    /**
     * Get Discount Label
     *
     * @return Phrase
     */
    public function getDiscountLabel()
    {
        return __("Save");
    }
}
