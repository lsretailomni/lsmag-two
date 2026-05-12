<?php
declare(strict_types=1);

namespace Ls\Webhooks\Api\Data;

/**
 * Interface OrderReturnsMessageInterface
 *
 * Represents the order returns payload received by the API.
 */
interface OrderReturnsMessageInterface
{
    public const ORDER_ID = 'OrderId';
    public const RETURN_TYPE = 'ReturnType';
    public const AMOUNT = 'Amount';
    public const LINES = 'Lines';

    /**
     * Get the order ID associated with the return.
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set the order ID associated with the return.
     *
     * @param string|null $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get the return type.
     *
     * @return string|null
     */
    public function getReturnType();

    /**
     * Set the return type.
     *
     * @param string|null $returnType
     * @return $this
     */
    public function setReturnType($returnType);

    /**
     * Get the return amount.
     *
     * @return string|null
     */
    public function getAmount();

    /**
     * Set the return amount.
     *
     * @param string|null $amount
     * @return $this
     */
    public function setAmount($amount);

    /**
     * Get the order lines included in this return.
     *
     * @return \Ls\Webhooks\Api\Data\OrderLineInterface[]|null
     */
    public function getLines();

    /**
     * Set the order lines included in this return.
     *
     * @param \Ls\Webhooks\Api\Data\OrderLineInterface[]|null $lines
     * @return $this
     */
    public function setLines(?array $lines = null);
}
