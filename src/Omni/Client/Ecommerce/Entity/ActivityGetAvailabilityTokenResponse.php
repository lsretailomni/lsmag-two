<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ActivityGetAvailabilityTokenResponse implements ResponseInterface
{

    /**
     * @property string $ActivityGetAvailabilityTokenResult
     */
    protected $ActivityGetAvailabilityTokenResult = null;

    /**
     * @param string $ActivityGetAvailabilityTokenResult
     * @return $this
     */
    public function setActivityGetAvailabilityTokenResult($ActivityGetAvailabilityTokenResult)
    {
        $this->ActivityGetAvailabilityTokenResult = $ActivityGetAvailabilityTokenResult;
        return $this;
    }

    /**
     * @return string
     */
    public function getActivityGetAvailabilityTokenResult()
    {
        return $this->ActivityGetAvailabilityTokenResult;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->ActivityGetAvailabilityTokenResult;
    }


}

