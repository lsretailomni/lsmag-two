<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class SalesEntryGetResponse implements ResponseInterface
{

    /**
     * @property SalesEntry $SalesEntryGetResult
     */
    protected $SalesEntryGetResult = null;

    /**
     * @param SalesEntry $SalesEntryGetResult
     * @return $this
     */
    public function setSalesEntryGetResult($SalesEntryGetResult)
    {
        $this->SalesEntryGetResult = $SalesEntryGetResult;
        return $this;
    }

    /**
     * @return SalesEntry
     */
    public function getSalesEntryGetResult()
    {
        return $this->SalesEntryGetResult;
    }

    /**
     * @return SalesEntry
     */
    public function getResult()
    {
        return $this->SalesEntryGetResult;
    }


}

