<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Data;

use Magento\Framework\Model\AbstractExtensibleModel;
use Ls\Webhooks\Api\Data\OrderPaymentResponseInterface;

class OrderPaymentResponse extends AbstractExtensibleModel implements OrderPaymentResponseInterface
{
    /**
     * @inheritdoc
     */
    public function getOrderMessagePaymentResult(): bool
    {
        return (bool) $this->getData(self::ORDER_MESSAGE_PAYMENT_RESULT);
    }

    /**
     * @inheritdoc
     */
    public function setOrderMessagePaymentResult(bool $result): self
    {
        return $this->setData(self::ORDER_MESSAGE_PAYMENT_RESULT, $result);
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        return (string) $this->getData(self::MESSAGE);
    }

    /**
     * @inheritdoc
     */
    public function setMessage(string $message): self
    {
        return $this->setData(self::MESSAGE, $message);
    }
}
