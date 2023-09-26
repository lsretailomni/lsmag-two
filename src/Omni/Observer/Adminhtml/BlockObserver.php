<?php

namespace Ls\Omni\Observer\Adminhtml;

use IntlDateFormatter;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;

/**
 * Class BlockObserver
 * @package Ls\Omni\Observer\Adminhtml
 */
class BlockObserver implements ObserverInterface
{
    /** @var TimezoneInterface */
    private $date;

    /** @var Template */
    private $coreTemplate;

    /**
     * BlockObserver constructor.
     * @param TimezoneInterface $date
     * @param Template $coreTemplate
     */
    public function __construct(
        TimezoneInterface $date,
        Template $coreTemplate
    ) {
        $this->date         = $date;
        $this->coreTemplate = $coreTemplate;
    }

    /**
     * @param EventObserver $observer
     * @return $this|void
     */

    public function execute(EventObserver $observer)
    {
        if ($observer->getElementName() == 'order_shipping_view') {
            $shippingInfoBlock = $observer->getLayout()->getBlock($observer->getElementName());
            $order             = $shippingInfoBlock->getOrder();

            if ($order->getShippingMethod() != 'clickandcollect_clickandcollect') {
                return $this;
            }

            $formattedDate = $this->date->formatDate($order->getPickupDate(), IntlDateFormatter::MEDIUM);
            $pickupInfo    = $this->coreTemplate
                ->setPickupDate($formattedDate)
                ->setPickupStore($order->getPickupStore())
                ->setTemplate('Ls_Omni::order/view/pickup-info.phtml')
                ->toHtml();
            $html          = $observer->getTransport()->getOutput() . $pickupInfo;
            $observer->getTransport()->setOutput($html);
        }
    }
}
