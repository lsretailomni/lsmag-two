<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class PaymentClientTokenGetResponse implements ResponseInterface
{
    /**
     * @property ClientToken $PaymentClientTokenGetResult
     */
    protected $PaymentClientTokenGetResult = null;

    /**
     * @param ClientToken $PaymentClientTokenGetResult
     * @return $this
     */
    public function setPaymentClientTokenGetResult($PaymentClientTokenGetResult)
    {
        $this->PaymentClientTokenGetResult = $PaymentClientTokenGetResult;
        return $this;
    }

    /**
     * @return ClientToken
     */
    public function getPaymentClientTokenGetResult()
    {
        return $this->PaymentClientTokenGetResult;
    }

    /**
     * @return ClientToken
     */
    public function getResult()
    {
        return $this->PaymentClientTokenGetResult;
    }
}

