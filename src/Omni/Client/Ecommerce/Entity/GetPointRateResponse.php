<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class GetPointRateResponse implements ResponseInterface
{
    /**
     * @property float $GetPointRateResult
     */
    protected $GetPointRateResult = null;

    /**
     * @param float $GetPointRateResult
     * @return $this
     */
    public function setGetPointRateResult($GetPointRateResult)
    {
        $this->GetPointRateResult = $GetPointRateResult;
        return $this;
    }

    /**
     * @return float
     */
    public function getGetPointRateResult()
    {
        return $this->GetPointRateResult;
    }

    /**
     * @return float
     */
    public function getResult()
    {
        return $this->GetPointRateResult;
    }
}

