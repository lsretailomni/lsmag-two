<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class OrderUpdatePaymentResponse implements ResponseInterface
{
    /**
     * @property boolean $OrderUpdatePaymentResult
     */
    protected $OrderUpdatePaymentResult = null;

    /**
     * @param boolean $OrderUpdatePaymentResult
     * @return $this
     */
    public function setOrderUpdatePaymentResult($OrderUpdatePaymentResult)
    {
        $this->OrderUpdatePaymentResult = $OrderUpdatePaymentResult;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getOrderUpdatePaymentResult()
    {
        return $this->OrderUpdatePaymentResult;
    }

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->OrderUpdatePaymentResult;
    }
}
