<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ActivityCustomerEntriesGetResponse implements ResponseInterface
{
    /**
     * @property ArrayOfCustomerEntry $ActivityCustomerEntriesGetResult
     */
    protected $ActivityCustomerEntriesGetResult = null;

    /**
     * @param ArrayOfCustomerEntry $ActivityCustomerEntriesGetResult
     * @return $this
     */
    public function setActivityCustomerEntriesGetResult($ActivityCustomerEntriesGetResult)
    {
        $this->ActivityCustomerEntriesGetResult = $ActivityCustomerEntriesGetResult;
        return $this;
    }

    /**
     * @return ArrayOfCustomerEntry
     */
    public function getActivityCustomerEntriesGetResult()
    {
        return $this->ActivityCustomerEntriesGetResult;
    }

    /**
     * @return ArrayOfCustomerEntry
     */
    public function getResult()
    {
        return $this->ActivityCustomerEntriesGetResult;
    }
}
