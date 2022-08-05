<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\OmniGraphQl\Helper\DataHelper;

/**
 * Interceptor to intercept PlaceOrder resolver methods
 */
class PlaceOrderPlugin
{
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * After plugin to set custom data in Order response
     *
     * @param mixed $subject
     * @param array $result
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve($subject, $result)
    {
        if (isset($result['order']) && isset($result['order']['order_number'])) {
            $order = $this->dataHelper->getOrderByIncrementId($result['order']['order_number']);
            $result['order']['document_id'] = !empty($order) && $order->getDocumentId() ? $order->getDocumentId() : '';
            $result['order']['pickup_store_id'] = !empty($order) && $order->getPickupStore() ?
                $order->getPickupStore() : '';
            $result['order']['pickup_store_name'] = !empty($order) && $order->getPickupStore() ?
                $this->dataHelper->getStoreNameById($order->getPickupStore()) : '';
            $result['order']['pickup_date_timeslot'] = !empty($order) && $order->getPickupDateTimeslot() ?
                $order->getPickupDateTimeslot() : '';
        }

        return $result;
    }
}
