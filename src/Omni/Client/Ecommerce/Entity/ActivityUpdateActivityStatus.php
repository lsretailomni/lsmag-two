<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityUpdateActivityStatus implements RequestInterface
{
    /**
     * @property string $activityNo
     */
    protected $activityNo = null;

    /**
     * @property string $setStatusCode
     */
    protected $setStatusCode = null;

    /**
     * @param string $activityNo
     * @return $this
     */
    public function setActivityNo($activityNo)
    {
        $this->activityNo = $activityNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getActivityNo()
    {
        return $this->activityNo;
    }

    /**
     * @param string $setStatusCode
     * @return $this
     */
    public function setSetStatusCode($setStatusCode)
    {
        $this->setStatusCode = $setStatusCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getSetStatusCode()
    {
        return $this->setStatusCode;
    }
}

