<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Data;

use \Ls\Webhooks\Api\Data\OrderStatusMessageInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class OrderMessage
 *
 * Implementation of OrderMessageInterface
 */
class OrderStatusMessage extends AbstractExtensibleModel implements OrderStatusMessageInterface
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
    public function getCardId()
    {
        return $this->getData(self::CARD_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCardId($cardId)
    {
        return $this->setData(self::CARD_ID, $cardId);
    }

    /**
     * @inheritdoc
     */
    public function getHeaderStatus()
    {
        return $this->getData(self::HEADER_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setHeaderStatus($headerStatus)
    {
        return $this->setData(self::HEADER_STATUS, $headerStatus);
    }

    /**
     * @inheritdoc
     */
    public function getMsgSubject()
    {
        return $this->getData(self::MSG_SUBJECT);
    }

    /**
     * @inheritdoc
     */
    public function setMsgSubject($msgSubject)
    {
        return $this->setData(self::MSG_SUBJECT, $msgSubject);
    }

    /**
     * @inheritdoc
     */
    public function getMsgDetail()
    {
        return $this->getData(self::MSG_DETAIL);
    }

    /**
     * @inheritdoc
     */
    public function setMsgDetail($msgDetail)
    {
        return $this->setData(self::MSG_DETAIL, $msgDetail);
    }

    /**
     * @inheritdoc
     */
    public function getExtOrderStatus()
    {
        return $this->getData(self::EXT_ORDER_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setExtOrderStatus($extOrderStatus)
    {
        return $this->setData(self::EXT_ORDER_STATUS, $extOrderStatus);
    }

    /**
     * @inheritdoc
     */
    public function getOrderKOTStatus()
    {
        return $this->getData(self::ORDER_KOT_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setOrderKOTStatus($orderKOTStatus)
    {
        return $this->setData(self::ORDER_KOT_STATUS, $orderKOTStatus);
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
