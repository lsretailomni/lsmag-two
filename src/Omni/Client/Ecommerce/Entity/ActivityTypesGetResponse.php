<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ActivityTypesGetResponse implements ResponseInterface
{
    /**
     * @property ArrayOfActivityType $ActivityTypesGetResult
     */
    protected $ActivityTypesGetResult = null;

    /**
     * @param ArrayOfActivityType $ActivityTypesGetResult
     * @return $this
     */
    public function setActivityTypesGetResult($ActivityTypesGetResult)
    {
        $this->ActivityTypesGetResult = $ActivityTypesGetResult;
        return $this;
    }

    /**
     * @return ArrayOfActivityType
     */
    public function getActivityTypesGetResult()
    {
        return $this->ActivityTypesGetResult;
    }

    /**
     * @return ArrayOfActivityType
     */
    public function getResult()
    {
        return $this->ActivityTypesGetResult;
    }
}

