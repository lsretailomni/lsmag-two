<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ActivityReservationInsertResponse implements ResponseInterface
{

    /**
     * @property string $ActivityReservationInsertResult
     */
    protected $ActivityReservationInsertResult = null;

    /**
     * @param string $ActivityReservationInsertResult
     * @return $this
     */
    public function setActivityReservationInsertResult($ActivityReservationInsertResult)
    {
        $this->ActivityReservationInsertResult = $ActivityReservationInsertResult;
        return $this;
    }

    /**
     * @return string
     */
    public function getActivityReservationInsertResult()
    {
        return $this->ActivityReservationInsertResult;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->ActivityReservationInsertResult;
    }


}

