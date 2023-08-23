<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityResourceAvailabilityGet implements RequestInterface
{
    /**
     * @property string $locationNo
     */
    protected $locationNo = null;

    /**
     * @property string $activityDate
     */
    protected $activityDate = null;

    /**
     * @property string $resourceNo
     */
    protected $resourceNo = null;

    /**
     * @property string $intervalType
     */
    protected $intervalType = null;

    /**
     * @property int $noOfDays
     */
    protected $noOfDays = null;

    /**
     * @param string $locationNo
     * @return $this
     */
    public function setLocationNo($locationNo)
    {
        $this->locationNo = $locationNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocationNo()
    {
        return $this->locationNo;
    }

    /**
     * @param string $activityDate
     * @return $this
     */
    public function setActivityDate($activityDate)
    {
        $this->activityDate = $activityDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getActivityDate()
    {
        return $this->activityDate;
    }

    /**
     * @param string $resourceNo
     * @return $this
     */
    public function setResourceNo($resourceNo)
    {
        $this->resourceNo = $resourceNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getResourceNo()
    {
        return $this->resourceNo;
    }

    /**
     * @param string $intervalType
     * @return $this
     */
    public function setIntervalType($intervalType)
    {
        $this->intervalType = $intervalType;
        return $this;
    }

    /**
     * @return string
     */
    public function getIntervalType()
    {
        return $this->intervalType;
    }

    /**
     * @param int $noOfDays
     * @return $this
     */
    public function setNoOfDays($noOfDays)
    {
        $this->noOfDays = $noOfDays;
        return $this;
    }

    /**
     * @return int
     */
    public function getNoOfDays()
    {
        return $this->noOfDays;
    }
}

