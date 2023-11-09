<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class CheckAvailability implements RequestInterface
{
    /**
     * @property ArrayOfHospAvailabilityRequest $request
     */
    protected $request = null;

    /**
     * @property string $storeId
     */
    protected $storeId = null;

    /**
     * @param ArrayOfHospAvailabilityRequest $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return ArrayOfHospAvailabilityRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->storeId;
    }
}

