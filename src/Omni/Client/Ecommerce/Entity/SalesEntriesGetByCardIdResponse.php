<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class SalesEntriesGetByCardIdResponse implements ResponseInterface
{
    /**
     * @property ArrayOfSalesEntry $SalesEntriesGetByCardIdResult
     */
    protected $SalesEntriesGetByCardIdResult = null;

    /**
     * @param ArrayOfSalesEntry $SalesEntriesGetByCardIdResult
     * @return $this
     */
    public function setSalesEntriesGetByCardIdResult($SalesEntriesGetByCardIdResult)
    {
        $this->SalesEntriesGetByCardIdResult = $SalesEntriesGetByCardIdResult;
        return $this;
    }

    /**
     * @return ArrayOfSalesEntry
     */
    public function getSalesEntriesGetByCardIdResult()
    {
        return $this->SalesEntriesGetByCardIdResult;
    }

    /**
     * @return ArrayOfSalesEntry
     */
    public function getResult()
    {
        return $this->SalesEntriesGetByCardIdResult;
    }
}

