<?php

namespace Ls\Customer\Controller\Order;

use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;

/**
 * Controller being used for customer order shipment
 */
class Shipment extends AbstractOrderController implements HttpGetActionInterface
{
    /**
     * @inheritDoc
     *
     * @return Page|ResultInterface|void
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->registerValuesInRegistry();

        if ($result) {
            return $result;
        }
        $this->setPrintShipmentOption();
        $this->orderHelper->registerGivenValueInRegistry('current_detail', 'shipment');
        $this->orderHelper->registerGivenValueInRegistry('hide_shipping_links', true);

        return $this->resultPageFactory->create();
    }

    /**
     *  Print Shipment Option
     */
    public function setPrintShipmentOption()
    {
        $order = $this->orderHelper->getGivenValueFromRegistry('current_mag_order');

        if (!empty($order)) {
            if (!empty($order->getShipmentsCollection())) {
                $this->orderHelper->registerGivenValueInRegistry('current_shipment_option', true);
            } else {
                $this->orderHelper->registerGivenValueInRegistry('current_shipment_option', false);
            }
        }
    }
}
