<?php
declare(strict_types=1);

namespace Ls\Webhooks\Api\Data;

/**
 * Order Payment Response Interface
 *
 * Represents the response returned by the OrderMessagePayment
 * REST endpoint after processing an order payment webhook.
 *
 * @api
 */
interface OrderPaymentResponseInterface
{
    public const ORDER_MESSAGE_PAYMENT_RESULT = 'OrderMessagePaymentResult';
    public const MESSAGE = 'message';
    /**
     * Get order payment result flag
     *
     * Indicates whether the order payment command
     * was successfully processed.
     *
     * @return bool
     */
    public function getOrderMessagePaymentResult(): bool;

    /**
     * Set order payment result flag
     *
     * @param bool $result
     * @return $this
     */
    public function setOrderMessagePaymentResult(bool $result): self;

    /**
     * Get response message
     *
     * Human-readable message describing the result
     * of the payment command processing.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Set response message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self;
}
