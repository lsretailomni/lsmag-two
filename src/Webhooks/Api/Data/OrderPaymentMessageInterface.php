<?php
declare(strict_types=1);

namespace Ls\Webhooks\Api\Data;

/**
 * Interface OrderPaymentInterface
 *
 * Represents the order payment payload received by the API.
 */
interface OrderPaymentMessageInterface
{
    public const ORDER_ID = 'OrderId';
    public const STATUS = 'Status';
    public const AMOUNT = 'Amount';
    public const CURRENCY_CODE = 'CurrencyCode';
    public const TOKEN = 'Token';
    public const AUTH_CODE = 'AuthCode';
    public const REFERENCE = 'Reference';
    public const LINES = 'Lines';

    /**
     * Get the order ID.
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set the order ID.
     *
     * @param string|null $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get the payment status.
     *
     * Example: 0 = Pending, 1 = Authorized, etc.
     *
     * @return int|null
     */
    public function getStatus();

    /**
     * Set the payment status.
     *
     * Example: 0 = Pending, 1 = Authorized, etc.
     *
     * @param int|null $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get the payment amount.
     *
     * @return float|null
     */
    public function getAmount();

    /**
     * Set the payment amount.
     *
     * @param float|null $amount
     * @return $this
     */
    public function setAmount($amount);

    /**
     * Get the currency code for the payment.
     *
     * Example: "GBP", "USD".
     *
     * @return string|null
     */
    public function getCurrencyCode();

    /**
     * Set the currency code for the payment.
     *
     * Example: "GBP", "USD".
     *
     * @param string|null $currencyCode
     * @return $this
     */
    public function setCurrencyCode($currencyCode);

    /**
     * Get the payment token.
     *
     * Usually used to reference the payment transaction.
     *
     * @return string|null
     */
    public function getToken();

    /**
     * Set the payment token.
     *
     * @param string|null $token
     * @return $this
     */
    public function setToken($token);

    /**
     * Get the authorization code.
     *
     * Example: Provided by payment gateway (e.g., "WebPreAuthOnPos").
     *
     * @return string|null
     */
    public function getAuthCode();

    /**
     * Set the authorization code.
     *
     * Example: Provided by payment gateway (e.g., "WebPreAuthOnPos").
     *
     * @param string|null $authCode
     * @return $this
     */
    public function setAuthCode($authCode);

    /**
     * Get the reference identifier for the payment.
     *
     * Example: "UR53-000000010".
     *
     * @return string|null
     */
    public function getReference();

    /**
     * Set the reference identifier for the payment.
     *
     * Example: "UR53-000000010".
     *
     * @param string|null $reference
     * @return $this
     */
    public function setReference($reference);

    /**
     * Get the order lines associated with this payment.
     *
     * @return \Ls\Webhooks\Api\Data\OrderLineInterface[]|null
     */
    public function getLines();

    /**
     * Set the order lines associated with this payment.
     *
     * @param \Ls\Webhooks\Api\Data\OrderLineInterface[]|null $lines
     * @return $this
     */
    public function setLines(array $lines = null);
}
