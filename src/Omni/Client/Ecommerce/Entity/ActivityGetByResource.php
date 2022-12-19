<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityGetByResource implements RequestInterface
{

    /**
     * @property string $locationNo
     */
    protected $locationNo = null;

    /**
     * @property string $resourceNo
     */
    protected $resourceNo = null;

    /**
     * @property string $fromDate
     */
    protected $fromDate = null;

    /**
     * @property string $toDate
     */
    protected $toDate = null;

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
     * @param string $fromDate
     * @return $this
     */
    public function setFromDate($fromDate)
    {
        $this->fromDate = $fromDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * @param string $toDate
     * @return $this
     */
    public function setToDate($toDate)
    {
        $this->toDate = $toDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getToDate()
    {
        return $this->toDate;
    }


}

