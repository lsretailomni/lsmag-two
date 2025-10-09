<?php
declare(strict_types=1);

namespace Ls\Webhooks\Api\Data;

/**
 * Interface OrderMessageInterface
 *
 * Represents the order message payload received by the API
 */
interface OrderStatusMessageInterface
{
    public const ORDER_ID = 'OrderId';
    public const CARD_ID = 'CardId';
    public const HEADER_STATUS = 'HeaderStatus';
    public const MSG_SUBJECT = 'MsgSubject';
    public const MSG_DETAIL = 'MsgDetail';
    public const EXT_ORDER_STATUS = 'ExtOrderStatus';
    public const ORDER_KOT_STATUS = 'OrderKOTStatus';
    public const LINES = 'Lines';

    /**
     * Retrieve the Order ID
     *
     * @return string|null The unique identifier for the order
     */
    public function getOrderId();

    /**
     * Set the Order ID
     *
     * @param string|null $orderId The unique identifier for the order
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Retrieve the Card ID
     *
     * @return string|null The ID of the card associated with the order
     */
    public function getCardId();

    /**
     * Set the Card ID
     *
     * @param string|null $cardId The ID of the card associated with the order
     * @return $this
     */
    public function setCardId($cardId);

    /**
     * Retrieve the header status
     *
     * @return string|null The status of the order header
     */
    public function getHeaderStatus();

    /**
     * Set the header status
     *
     * @param string|null $headerStatus The status of the order header
     * @return $this
     */
    public function setHeaderStatus($headerStatus);

    /**
     * Retrieve the message subject
     *
     * @return string|null The subject of the order message
     */
    public function getMsgSubject();

    /**
     * Set the message subject
     *
     * @param string|null $msgSubject The subject of the order message
     * @return $this
     */
    public function setMsgSubject($msgSubject);

    /**
     * Retrieve the message detail
     *
     * @return string|null The detailed content of the order message
     */
    public function getMsgDetail();

    /**
     * Set the message detail
     *
     * @param string|null $msgDetail The detailed content of the order message
     * @return $this
     */
    public function setMsgDetail($msgDetail);

    /**
     * Retrieve the external order status
     *
     * @return string|null Status of the order from the external system
     */
    public function getExtOrderStatus();

    /**
     * Set the external order status
     *
     * @param string|null $extOrderStatus Status of the order from the external system
     * @return $this
     */
    public function setExtOrderStatus($extOrderStatus);

    /**
     * Retrieve the Order KOT (Kitchen Order Ticket) status
     *
     * @return string|null The KOT status of the order
     */
    public function getOrderKOTStatus();

    /**
     * Set the Order KOT (Kitchen Order Ticket) status
     *
     * @param string|null $orderKOTStatus The KOT status of the order
     * @return $this
     */
    public function setOrderKOTStatus($orderKOTStatus);

    /**
     * Retrieve the order lines
     *
     * @return \Ls\Webhooks\Api\Data\OrderLineInterface[]|null List of order line items
     */
    public function getLines();

    /**
     * Set the order lines
     *
     * @param \Ls\Webhooks\Api\Data\OrderLineInterface[]|null $lines List of order line items
     * @return $this
     */
    public function setLines(array $lines = null);
}
