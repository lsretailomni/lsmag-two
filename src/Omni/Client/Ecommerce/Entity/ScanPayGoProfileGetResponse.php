<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ScanPayGoProfileGetResponse implements ResponseInterface
{
    /**
     * @property ScanPayGoProfile $ScanPayGoProfileGetResult
     */
    protected $ScanPayGoProfileGetResult = null;

    /**
     * @param ScanPayGoProfile $ScanPayGoProfileGetResult
     * @return $this
     */
    public function setScanPayGoProfileGetResult($ScanPayGoProfileGetResult)
    {
        $this->ScanPayGoProfileGetResult = $ScanPayGoProfileGetResult;
        return $this;
    }

    /**
     * @return ScanPayGoProfile
     */
    public function getScanPayGoProfileGetResult()
    {
        return $this->ScanPayGoProfileGetResult;
    }

    /**
     * @return ScanPayGoProfile
     */
    public function getResult()
    {
        return $this->ScanPayGoProfileGetResult;
    }
}

