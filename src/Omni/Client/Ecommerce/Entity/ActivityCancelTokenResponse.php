<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ActivityCancelTokenResponse implements ResponseInterface
{
    /**
     * @property boolean $ActivityCancelTokenResult
     */
    protected $ActivityCancelTokenResult = null;

    /**
     * @param boolean $ActivityCancelTokenResult
     * @return $this
     */
    public function setActivityCancelTokenResult($ActivityCancelTokenResult)
    {
        $this->ActivityCancelTokenResult = $ActivityCancelTokenResult;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getActivityCancelTokenResult()
    {
        return $this->ActivityCancelTokenResult;
    }

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->ActivityCancelTokenResult;
    }
}

