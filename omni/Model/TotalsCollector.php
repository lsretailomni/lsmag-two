<?php
/**
 * Created by PhpStorm.
 * User: florin
 * Date: 26/07/2017
 * Time: 10:54
 */

namespace Ls\Omni\Model;

use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Client\Ecommerce\Entity;
use Magento\Quote\Model\Quote\Address\Total\Collector;
use Magento\Quote\Model\Quote\Address\Total\CollectorFactory;

class TotalsCollector extends \Magento\Quote\Model\Quote\TotalsCollector
{
    protected $basketHelper;

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
        parent::__construct($totalCollector, $totalCollectorFactory, $eventManager, $storeManager, $totalFactory,
            $collectorList, $shippingFactory, $shippingAssignmentFactory, $quoteValidator);
        $this->basketHelper = $basketHelper;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Model\Quote\Address\Total
     */
    public function collect(\Magento\Quote\Model\Quote $quote)
    {
        // logic partly taken from Magento\Quote\Model\Quote\TotalsCollector->collect()

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
            throw new Exception("Failure in Quote calculation.");
        }
        foreach ($addresses as $address) {
            // taken from parent::collectAddressTotals
            $this->collectAddressTotals($quote, $address);
            //if ($address->getAddressType() == "shipping") {
            //    $address->addData($total->getData());
            //}
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
        // needs to do the job of Magento/Quote/Model/Quote/TotalsCollector::collectAddressTotals
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

        // calculate totals
        // (base)GrandTotal is after tax, (base)SubTotal is before tax
        // (sub/grand)Total is in customer currency, base(Sub/Grand)total in shop currency
        // https://stackoverflow.com/questions/9704556/what-is-the-difference-between-subtotal-and-basesubtotal
        // we don't support multiple currencies yet, so base = non-base
        // in LS Mag 1, we did
        // 'currency' => $basket_calculation->getCurrencyCode()
        // 'total_amount' => $basket_calculation->getTotalAmount()
        // 'total_discount_amount' => $basket_calculation->getTotalDiscAmount()
        // 'total_net_amount' => $basket_calculation->getTotalNetAmount()
        // 'total_tax_amount' => $basket_calculation->getTotalTaxAmount()
        // 'order_base_subtotal' => $order->getBaseSubtotal()
        // 'order_discount_amount' => $order->getDiscountAmount()
        // 'order_grand_total' => $order->getGrandTotal()
        // 'order_shipping_amount' => $order->getShippingAmount()

        // TODO: add shipping

        #$total->setShippingAmount($basketCalc->getShippingAmount());
        #$total->setBaseShippingAmount($basketCalc->getShippingAmount());
        #$total->setShippingDescription($this->basketHelper->getShipmentFeeProduct()->getDescription());
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