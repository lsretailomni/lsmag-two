<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Data\Webapi;

use Ls\Webhooks\Api\Data\OrderPaymentResponseInterface;

class ChangeOutputArray
{
    /**
     * Changing order message payment result for webapi order payment response.
     *
     * @param OrderPaymentResponseInterface $dataObject
     * @param array $result
     * @return array
     */
    public function execute(
        OrderPaymentResponseInterface $dataObject,
        array $result
    ): array {
        $result[OrderPaymentResponseInterface::ORDER_MESSAGE_PAYMENT_RESULT] =
            $dataObject->getOrderMessagePaymentResult();
        unset($result['order_message_payment_result']);

        return $result;
    }
}
