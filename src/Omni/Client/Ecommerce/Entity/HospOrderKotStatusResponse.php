<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class HospOrderKotStatusResponse implements ResponseInterface
{

    /**
     * @property OrderHospStatus $HospOrderKotStatusResult
     */
    protected $HospOrderKotStatusResult = null;

    /**
     * @param OrderHospStatus $HospOrderKotStatusResult
     * @return $this
     */
    public function setHospOrderKotStatusResult($HospOrderKotStatusResult)
    {
        $this->HospOrderKotStatusResult = $HospOrderKotStatusResult;
        return $this;
    }

    /**
     * @return OrderHospStatus
     */
    public function getHospOrderKotStatusResult()
    {
        return $this->HospOrderKotStatusResult;
    }

    /**
     * @return OrderHospStatus
     */
    public function getResult()
    {
        return $this->HospOrderKotStatusResult;
    }


}
