<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ActivityProductChargesGetResponse implements ResponseInterface
{
    /**
     * @property ArrayOfAdditionalCharge $ActivityProductChargesGetResult
     */
    protected $ActivityProductChargesGetResult = null;

    /**
     * @param ArrayOfAdditionalCharge $ActivityProductChargesGetResult
     * @return $this
     */
    public function setActivityProductChargesGetResult($ActivityProductChargesGetResult)
    {
        $this->ActivityProductChargesGetResult = $ActivityProductChargesGetResult;
        return $this;
    }

    /**
     * @return ArrayOfAdditionalCharge
     */
    public function getActivityProductChargesGetResult()
    {
        return $this->ActivityProductChargesGetResult;
    }

    /**
     * @return ArrayOfAdditionalCharge
     */
    public function getResult()
    {
        return $this->ActivityProductChargesGetResult;
    }
}

