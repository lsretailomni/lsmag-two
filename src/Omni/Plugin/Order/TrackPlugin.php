<?php

namespace Ls\Omni\Plugin\Order;

use Magento\Framework\Api\AttributeValueFactory;

/**
 * Plugin to intercept Shipment Track details load
 * and append ls central shipping id
 */
class TrackPlugin extends \Magento\Sales\Model\Order\Shipment\Track
{
    public function __construct(
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Sales\Model\Order\Shipment\Track $trackShipment
    ) {
        $this->_carrierFactory = $carrierFactory;
        $this->trackShipment = $trackShipment;
    }
    /**
     * Retrieve detail for shipment track with ls central shipping id
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function afterGetNumberDetail(
        \Magento\Shipping\Model\Order\Track $subject,
        $proceed
    )
    {

        if(is_array($proceed)) {
            $proceed['ls_central_shipping_id'] = $subject->getLsCentralShippingId();
        }
        return $proceed;
    }
}
