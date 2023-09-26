<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityCustomerEntriesGet implements RequestInterface
{
    /**
     * @property string $contactNo
     */
    protected $contactNo = null;

    /**
     * @property string $customerNo
     */
    protected $customerNo = null;

    /**
     * @param string $contactNo
     * @return $this
     */
    public function setContactNo($contactNo)
    {
        $this->contactNo = $contactNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getContactNo()
    {
        return $this->contactNo;
    }

    /**
     * @param string $customerNo
     * @return $this
     */
    public function setCustomerNo($customerNo)
    {
        $this->customerNo = $customerNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerNo()
    {
        return $this->customerNo;
    }
}

