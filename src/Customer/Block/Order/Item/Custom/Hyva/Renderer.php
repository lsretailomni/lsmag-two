<?php
declare(strict_types=1);

namespace Ls\Customer\Block\Order\Item\Custom\Hyva;

use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\Omni\Model\VendorSourcing\LeadTimeParser;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Tax\Model\Config as TaxConfig;

class Renderer extends DefaultRenderer
{
    private const LEAD_TIME_KEY = 'Lead Time';

    private const QUANTITY_RECEIVED_KEY = 'Quantity Received';

    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/item/custom/renderer-hyva.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * @param Context $context
     * @param StringUtils $string
     * @param OptionFactory $productOptionFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param ItemHelper $itemHelper
     * @param CollectionFactory $itemCollectionFactory
     * @param OrderHelper $orderHelper
     * @param TaxConfig $taxConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        StringUtils $string,
        OptionFactory $productOptionFactory,
        public PriceCurrencyInterface $priceCurrency,
        public ItemHelper $itemHelper,
        public CollectionFactory $itemCollectionFactory,
        public OrderHelper $orderHelper,
        public TaxConfig $taxConfig,
        array $data = []
    ) {
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
        $result = [];
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
                        $orderItem->getSku()
                    );
                    if ($itemId == $centralItem->getNumber() &&
                        $variantId == $centralItem->getVariantCode() &&
                        $uom == $centralItem->getUnitOfMeasure()
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
     * Get the line holding the Central lead time / received qty for the current item
     *
     * Once an order is completed Central returns the posted receipt line (no lead time) next to the
     * original customer order line (which carries it), so fall back to the same product's line of
     * the other document, matched by position among lines of the same product.
     *
     * @return DataObject
     */
    private function getSourcingLine()
    {
        $item = $this->getItem();

        if (trim((string)$item->getData(self::LEAD_TIME_KEY)) !== '' ||
            (float)$item->getData(self::QUANTITY_RECEIVED_KEY) > 0
        ) {
            return $item;
        }

        $order = $this->orderHelper->getOrder(true);
        $lines = $order ? $order->getLscMemberSalesDocLine() : [];
        $lines = is_array($lines) ? $lines : ($lines ? [$lines] : []);
        $matches = static fn ($line, $sameDocument) => $line->getEntryType() == 0 &&
            $line->getNumber() == $item->getNumber() &&
            $line->getVariantCode() == $item->getVariantCode() &&
            $line->getUnitOfMeasure() == $item->getUnitOfMeasure() &&
            ($line->getDocumentId() == $item->getDocumentId()) === $sameDocument;

        $position = 0;
        foreach (array_filter($lines, fn ($line) => $matches($line, true)) as $line) {
            if ($line === $item || $line->getLineNo() == $item->getLineNo()) {
                break;
            }
            $position++;
        }

        $others = array_values(array_filter($lines, fn ($line) => $matches($line, false)));

        return $others[$position] ?? $item;
    }

    /**
     * Get the estimated delivery time of the order line, derived from the Central lead time
     *
     * @return Phrase|null
     */
    public function getEstimatedDeliveryTime()
    {
        $days = (new LeadTimeParser())->parse((string)$this->getSourcingLine()->getData(self::LEAD_TIME_KEY));

        if (!$days) {
            return null;
        }

        return $days === 1 ? __('1 day') : __('%1 days', $days);
    }

    /**
     * Get the received quantity of the order line, only for lines that have a lead time or received qty
     *
     * @return string|null
     */
    public function getQuantityReceived()
    {
        $item     = $this->getSourcingLine();
        $received = (float)$item->getData(self::QUANTITY_RECEIVED_KEY);

        if ($received <= 0 && trim((string)$item->getData(self::LEAD_TIME_KEY)) === '') {
            return null;
        }

        return $this->getFormattedQty($received);
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
     * @throws NoSuchEntityException
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
        $item = $this->getItem();
        $orderData = $this->orderHelper->getOrder();

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

    /**
     * For checking is shipment tab
     *
     * @return bool
     */
    public function checkIsShipment()
    {
        return ($this->orderHelper->getGivenValueFromRegistry('current_detail') == 'shipment');
    }

    /**
     * Whether the Price cell should show the tax-inclusive amount
     *
     * @return bool
     */
    public function isDisplaySalesPricesInclTax()
    {
        return (bool) $this->taxConfig->displaySalesPricesInclTax();
    }

    /**
     * Whether the Price cell should show the tax-exclusive amount
     *
     * @return bool
     */
    public function isDisplaySalesPricesExclTax()
    {
        return (bool) $this->taxConfig->displaySalesPricesExclTax();
    }

    /**
     * Whether the Price cell should show both tax-inclusive and tax-exclusive amounts
     *
     * @return bool
     */
    public function isDisplaySalesPricesBoth()
    {
        return (bool) $this->taxConfig->displaySalesPricesBoth();
    }

    /**
     * Whether the Subtotal cell should show the tax-inclusive amount
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalInclTax()
    {
        return (bool) $this->taxConfig->displaySalesSubtotalInclTax();
    }

    /**
     * Whether the Subtotal cell should show the tax-exclusive amount
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalExclTax()
    {
        return (bool) $this->taxConfig->displaySalesSubtotalExclTax();
    }

    /**
     * Whether the Subtotal cell should show both tax-inclusive and tax-exclusive amounts
     *
     * @return bool
     */
    public function isDisplaySalesSubtotalBoth()
    {
        return (bool) $this->taxConfig->displaySalesSubtotalBoth();
    }

    /**
     * Add VAT to a tax-exclusive LS Central amount to get its tax-inclusive counterpart.
     *
     * LS Central does not fold tax into Amount/NetAmount for US sales-tax stores (Amount and
     * NetAmount come back equal, with the tax tracked only in the line's own VatAmount), so the
     * "Incl. Tax" spans must add VatAmount explicitly rather than reading Amount alone.
     *
     * @param float|null $amount
     * @param float|null $vatAmount
     * @return float
     */
    public function getInclTaxAmount($amount, $vatAmount)
    {
        return (float)$amount + (float)$vatAmount;
    }
}
