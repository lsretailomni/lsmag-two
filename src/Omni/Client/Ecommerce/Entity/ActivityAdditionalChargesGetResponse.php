<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ActivityAdditionalChargesGetResponse implements ResponseInterface
{
    /**
     * @property ArrayOfAdditionalCharge $ActivityAdditionalChargesGetResult
     */
    protected $ActivityAdditionalChargesGetResult = null;

    /**
     * @param ArrayOfAdditionalCharge $ActivityAdditionalChargesGetResult
     * @return $this
     */
    public function setActivityAdditionalChargesGetResult($ActivityAdditionalChargesGetResult)
    {
        $this->ActivityAdditionalChargesGetResult = $ActivityAdditionalChargesGetResult;
        return $this;
    }

    /**
     * @return ArrayOfAdditionalCharge
     */
    public function getActivityAdditionalChargesGetResult()
    {
        return $this->ActivityAdditionalChargesGetResult;
    }

    /**
     * @return ArrayOfAdditionalCharge
     */
    public function getResult()
    {
        return $this->ActivityAdditionalChargesGetResult;
    }
}

