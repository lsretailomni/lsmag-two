<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityGroupAdditionalChargesSet implements RequestInterface
{
    /**
     * @property AdditionalCharge $reqest
     */
    protected $reqest = null;

    /**
     * @param AdditionalCharge $reqest
     * @return $this
     */
    public function setReqest($reqest)
    {
        $this->reqest = $reqest;
        return $this;
    }

    /**
     * @return AdditionalCharge
     */
    public function getReqest()
    {
        return $this->reqest;
    }
}

