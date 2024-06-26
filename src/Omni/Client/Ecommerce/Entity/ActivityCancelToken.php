<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityCancelToken implements RequestInterface
{
    /**
     * @property string $tokenId
     */
    protected $tokenId = null;

    /**
     * @param string $tokenId
     * @return $this
     */
    public function setTokenId($tokenId)
    {
        $this->tokenId = $tokenId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTokenId()
    {
        return $this->tokenId;
    }
}

