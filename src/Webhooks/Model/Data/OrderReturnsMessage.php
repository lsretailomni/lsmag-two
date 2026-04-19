<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Data;

use \Ls\Webhooks\Api\Data\OrderReturnsMessageInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class OrderReturnsMessage
 *
 * Implementation of OrderReturnsMessageInterface
 */
class OrderReturnsMessage extends AbstractExtensibleModel implements OrderReturnsMessageInterface
{
    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getReturnType()
    {
        return $this->getData(self::RETURN_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setReturnType($returnType)
    {
        return $this->setData(self::RETURN_TYPE, $returnType);
    }

    /**
     * @inheritdoc
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * @inheritdoc
     */
    public function getLines()
    {
        return $this->getData(self::LINES);
    }

    /**
     * @inheritdoc
     */
    public function setLines(?array $lines = null)
    {
        return $this->setData(self::LINES, $lines);
    }
}
