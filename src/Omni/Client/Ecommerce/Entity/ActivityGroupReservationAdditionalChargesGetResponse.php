<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ActivityGroupReservationAdditionalChargesGetResponse implements ResponseInterface
{
    /**
     * @property ArrayOfAdditionalCharge
     * $ActivityGroupReservationAdditionalChargesGetResult
     */
    protected $ActivityGroupReservationAdditionalChargesGetResult = null;

    /**
     * @param ArrayOfAdditionalCharge
     * $ActivityGroupReservationAdditionalChargesGetResult
     * @return $this
     */
    public function setActivityGroupReservationAdditionalChargesGetResult($ActivityGroupReservationAdditionalChargesGetResult)
    {
        $this->ActivityGroupReservationAdditionalChargesGetResult = $ActivityGroupReservationAdditionalChargesGetResult;
        return $this;
    }

    /**
     * @return ArrayOfAdditionalCharge
     */
    public function getActivityGroupReservationAdditionalChargesGetResult()
    {
        return $this->ActivityGroupReservationAdditionalChargesGetResult;
    }

    /**
     * @return ArrayOfAdditionalCharge
     */
    public function getResult()
    {
        return $this->ActivityGroupReservationAdditionalChargesGetResult;
    }
}

