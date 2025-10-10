<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Data;

use Ls\Webhooks\Api\Data\OrderPaymentMessageInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class OrderPayment
 *
 * Implementation of OrderPaymentInterface
 */
class OrderPaymentMessage extends AbstractExtensibleModel implements OrderPaymentMessageInterface
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
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
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
    public function getCurrencyCode()
    {
        return $this->getData(self::CURRENCY_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setCurrencyCode($currencyCode)
    {
        return $this->setData(self::CURRENCY_CODE, $currencyCode);
    }

    /**
     * @inheritdoc
     */
    public function getToken()
    {
        return $this->getData(self::TOKEN);
    }

    /**
     * @inheritdoc
     */
    public function setToken($token)
    {
        return $this->setData(self::TOKEN, $token);
    }

    /**
     * @inheritdoc
     */
    public function getAuthCode()
    {
        return $this->getData(self::AUTH_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setAuthCode($authCode)
    {
        return $this->setData(self::AUTH_CODE, $authCode);
    }

    /**
     * @inheritdoc
     */
    public function getReference()
    {
        return $this->getData(self::REFERENCE);
    }

    /**
     * @inheritdoc
     */
    public function setReference($reference)
    {
        return $this->setData(self::REFERENCE, $reference);
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
