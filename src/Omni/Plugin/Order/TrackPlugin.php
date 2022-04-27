<?php

namespace Ls\Omni\Plugin\Order;

use Magento\Shipping\Model\Order\Track;

/**
 * Plugin to intercept Shipment Track details load
 * and append ls central shipping id
 */
class TrackPlugin extends \Magento\Sales\Model\Order\Shipment\Track
{
    /**
     * Retrieve detail for shipment track with ls central shipping id
     * @param Track $subject
     * @param $proceed
     * @return mixed
     */
    public function afterGetNumberDetail(
        Track $subject,
        $proceed
    ) {
        if (is_array($proceed)) {
            $proceed['ls_central_shipping_id'] = $subject->getLsCentralShippingId();
        }
        return $proceed;
    }
}
