<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class PingResponse implements ResponseInterface
{

    /**
     * @property string $PingResult
     */
    protected $PingResult = null;

    /**
     * @param string $PingResult
     * @return $this
     */
    public function setPingResult($PingResult)
    {
        $this->PingResult = $PingResult;
        return $this;
    }

    /**
     * @return string
     */
    public function getPingResult()
    {
        return $this->PingResult;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->PingResult;
    }


}

