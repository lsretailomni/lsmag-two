<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommImageLinksResponse implements ResponseInterface
{
    /**
     * @property ReplImageLinkResponse $ReplEcommImageLinksResult
     */
    protected $ReplEcommImageLinksResult = null;

    /**
     * @param ReplImageLinkResponse $ReplEcommImageLinksResult
     * @return $this
     */
    public function setReplEcommImageLinksResult($ReplEcommImageLinksResult)
    {
        $this->ReplEcommImageLinksResult = $ReplEcommImageLinksResult;
        return $this;
    }

    /**
     * @return ReplImageLinkResponse
     */
    public function getReplEcommImageLinksResult()
    {
        return $this->ReplEcommImageLinksResult;
    }

    /**
     * @return ReplImageLinkResponse
     */
    public function getResult()
    {
        return $this->ReplEcommImageLinksResult;
    }
}

