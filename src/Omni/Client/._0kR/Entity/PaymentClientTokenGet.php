<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class PaymentClientTokenGet implements RequestInterface
{
    /**
     * @property string $customerId
     */
    protected $customerId = null;

    /**
     * @param string $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }
}
