<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class StoresGetAllResponse implements ResponseInterface
{
    /**
     * @property ArrayOfStore $StoresGetAllResult
     */
    protected $StoresGetAllResult = null;

    /**
     * @param ArrayOfStore $StoresGetAllResult
     * @return $this
     */
    public function setStoresGetAllResult($StoresGetAllResult)
    {
        $this->StoresGetAllResult = $StoresGetAllResult;
        return $this;
    }

    /**
     * @return ArrayOfStore
     */
    public function getStoresGetAllResult()
    {
        return $this->StoresGetAllResult;
    }

    /**
     * @return ArrayOfStore
     */
    public function getResult()
    {
        return $this->StoresGetAllResult;
    }
}
