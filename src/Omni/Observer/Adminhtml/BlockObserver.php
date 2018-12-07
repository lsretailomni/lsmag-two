<?php
namespace Ls\Omni\Observer\Adminhtml;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class BlockObserver implements ObserverInterface
{
    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface  */
    protected $_date;

    /** @var \Magento\Framework\View\Element\Template  */
    protected $_coreTemplate;

    /**
     * BlockObserver constructor.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
     * @param \Magento\Framework\View\Element\Template $coreTemplate
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Framework\View\Element\Template $coreTemplate
    ) {
        $this->_date = $date;
        $this->_coreTemplate = $coreTemplate;
    }

    /**
     * @param EventObserver $observer
     * @return $this|void
     */

    public function execute(EventObserver $observer)
    {
        if ($observer->getElementName() == 'order_shipping_view') {
            $shippingInfoBlock = $observer->getLayout()->getBlock($observer->getElementName());
            $order = $shippingInfoBlock->getOrder();

            if ($order->getShippingMethod() != 'clickandcollect_clickandcollect') {
                return $this;
            }

            $formattedDate = $this->_date->formatDate($order->getPickupDate(), \IntlDateFormatter::MEDIUM);
            $pickupInfo = $this->_coreTemplate
                ->setPickupDate($formattedDate)
                ->setPickupStore($order->getPickupStore())
                ->setTemplate('Ls_Omni::order/view/pickup-info.phtml')
                ->toHtml();
            $html = $observer->getTransport()->getOutput() . $pickupInfo;
            $observer->getTransport()->setOutput($html);
        }
    }
}
