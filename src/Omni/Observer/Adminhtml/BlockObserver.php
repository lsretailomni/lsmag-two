<?php
declare(strict_types=1);

namespace Ls\Omni\Observer\Adminhtml;

use IntlDateFormatter;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;

class BlockObserver implements ObserverInterface
{
    /**
     * @param TimezoneInterface $date
     * @param Template $coreTemplate
     */
    public function __construct(
        public TimezoneInterface $date,
        public Template $coreTemplate
    ) {
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
