<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class TokenEntrySetResponse implements ResponseInterface
{
    /**
     * @property boolean $TokenEntrySetResult
     */
    protected $TokenEntrySetResult = null;

    /**
     * @param boolean $TokenEntrySetResult
     * @return $this
     */
    public function setTokenEntrySetResult($TokenEntrySetResult)
    {
        $this->TokenEntrySetResult = $TokenEntrySetResult;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getTokenEntrySetResult()
    {
        return $this->TokenEntrySetResult;
    }

    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->TokenEntrySetResult;
    }
}
