<?php

namespace Ls\Omni\Model;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Client\Ecommerce\Entity;
use Magento\Quote\Model\Quote\Address\Total\Collector;
use Magento\Quote\Model\Quote\Address\Total\CollectorFactory;

/**
 * Class TotalsCollector
 * @package Ls\Omni\Model
 */
class TotalsCollector extends \Magento\Quote\Model\Quote\TotalsCollector
{
    /** @var BasketHelper  */
    public $basketHelper;

    /**
     * ***** Important ***********
     * We are not using this uptill now for Omni Calculation, but might be planning to use this while handling complex pricing management.
     *
     */

    /**
     * TotalsCollector constructor.
     * @param Collector $totalCollector
     * @param CollectorFactory $totalCollectorFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Model\Quote\Address\TotalFactory $totalFactory
     * @param \Magento\Quote\Model\Quote\TotalsCollectorList $collectorList
     * @param \Magento\Quote\Model\ShippingFactory $shippingFactory
     * @param \Magento\Quote\Model\ShippingAssignmentFactory $shippingAssignmentFactory
     * @param \Magento\Quote\Model\QuoteValidator $quoteValidator
     * @param BasketHelper $basketHelper
     */

    public function __construct(
        Collector $totalCollector,
        CollectorFactory $totalCollectorFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\Quote\Address\TotalFactory $totalFactory,
        \Magento\Quote\Model\Quote\TotalsCollectorList $collectorList,
        \Magento\Quote\Model\ShippingFactory $shippingFactory,
        \Magento\Quote\Model\ShippingAssignmentFactory $shippingAssignmentFactory,
        \Magento\Quote\Model\QuoteValidator $quoteValidator,
        BasketHelper $basketHelper
    ) {
        parent::__construct(
            $totalCollector,
            $totalCollectorFactory,
            $eventManager,
            $storeManager,
            $totalFactory,
            $collectorList,
            $shippingFactory,
            $shippingAssignmentFactory,
            $quoteValidator
        );
        $this->basketHelper = $basketHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Model\Quote\Address\Total
     */
    public function collect(\Magento\Quote\Model\Quote $quote)
    {

        /** @var \Magento\Quote\Model\Quote\Address\Total $total */
        $total = $this->totalFactory->create('Magento\Quote\Model\Quote\Address\Total');

        $this->eventManager->dispatch(
            'sales_quote_collect_totals_before',
            ['quote' => $this]
        );

        // handle addresses
        $addresses = $quote->getAllAddresses();
        // we should have one billing and one delivery address
        if (count($addresses) != 2) {
            // uh-oh, probably multi-address checkout, we can't handle this yet
            throw new \Exception("Failure in Quote calculation.");
        }
        foreach ($addresses as $address) {
            // taken from parent::collectAddressTotals
            $this->collectAddressTotals($quote, $address);
        }

        $this->quoteValidator->validateQuoteAmount($quote, $quote->getGrandTotal());
        $this->quoteValidator->validateQuoteAmount($quote, $quote->getBaseGrandTotal());
        $this->eventManager->dispatch(
            'sales_quote_collect_totals_after',
            ['quote' => $quote]
        );

        return $total;
    }

    public function collectAddressTotals(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address $address)
    {
        $shippingAssignment = $this->shippingAssignmentFactory->create();

        /** @var \Magento\Quote\Api\Data\ShippingInterface $shipping */
        $shipping = $this->shippingFactory->create();
        $shipping->setMethod($address->getShippingMethod());
        $shipping->setAddress($address);
        $shippingAssignment->setShipping($shipping);
        $shippingAssignment->setItems($address->getAllItems());

        /** @var \Magento\Quote\Model\Quote\Address\Total $total */
        $total = $this->totalFactory->create('Magento\Quote\Model\Quote\Address\Total');
        $this->eventManager->dispatch(
            'sales_quote_address_collect_totals_before',
            [
                'quote' => $quote,
                'shipping_assignment' => $shippingAssignment,
                'total' => $total
            ]
        );

        // fetch calculation via BasketHelper
        /** @var Entity\BasketCalcResponse $basketCalc */
        $basketCalc = $this->basketHelper->getOneListCalculation();

        $this->_collectItemsQtys($quote);

        // check if sums add up
        $quoteSum = 0;
        /** @var \Magento\Quote\Model\ResourceModel\Quote\Item $item */
        foreach ($quote->getAllItems() as $item) {
            /** @var \Magento\Catalog\Model\Product\Interceptor $product */
            $product = $item->getProduct();
            $price = $product->getPrice();
            $qty = $item->getQty();
            $sum = $price*$qty;
            $quoteSum += $sum;
        }
        // @codingStandardsIgnoreStart
        if ($quoteSum != $basketCalc->getTotalAmount()) {
            // TODO: what to do now? Discounts maybe not included?
        }
        // @codingStandardsIgnoreEnd

        $total->setShippingAmount(5);
        $total->setBaseShippingAmount(5);
        $total->setShippingAmountInclTax(5);
        $total->setBaseShippingTaxAmount(0);
        $total->setShippingTaxAmount(0);
        $total->setShippingDescription("Shipping");

        // customer currency, before tax
        $total->setSubtotal((float)$basketCalc->getTotalNetAmount());
        // shop currency, before tax
        $total->setBaseSubtotal((float)$basketCalc->getTotalNetAmount());

        // customer currency, without shipping, after tax
        $total->setSubtotalInclTax((float)$basketCalc->getTotalAmount()-$total->getShippingAmount());

        // customer currency, with discount, before tax
        $total->setSubtotalWithDiscount((float)$basketCalc->getTotalNetAmount()-$basketCalc->getTotalDiscAmount());
        // shop currency, with discount, before tax
        $total->setBaseSubtotalWithDiscount((float)$basketCalc->getTotalNetAmount()-$basketCalc->getTotalDiscAmount());

        // discount
        $total->setDiscountAmount($basketCalc->getTotalDiscAmount());

        $total->setTaxAmount($basketCalc->getTotalTaxAmount());

        // after tax, customer currency
        $total->setGrandTotal((float)$basketCalc->getTotalAmount());
        // after tax, shop currency
        $total->setBaseGrandTotal((float)$basketCalc->getTotalNetAmount());

        $this->eventManager->dispatch(
            'sales_quote_address_collect_totals_after',
            [
                'quote' => $quote,
                'shipping_assignment' => $shippingAssignment,
                'total' => $total
            ]
        );

        $address->addData($total->getData());
        $address->setAppliedTaxes($total->getAppliedTaxes());
        return $total;
    }
}
