<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommStoreTenderTypesResponse implements ResponseInterface
{
    /**
     * @property ReplStoreTenderTypeResponse $ReplEcommStoreTenderTypesResult
     */
    protected $ReplEcommStoreTenderTypesResult = null;

    /**
     * @param ReplStoreTenderTypeResponse $ReplEcommStoreTenderTypesResult
     * @return $this
     */
    public function setReplEcommStoreTenderTypesResult($ReplEcommStoreTenderTypesResult)
    {
        $this->ReplEcommStoreTenderTypesResult = $ReplEcommStoreTenderTypesResult;
        return $this;
    }

    /**
     * @return ReplStoreTenderTypeResponse
     */
    public function getReplEcommStoreTenderTypesResult()
    {
        return $this->ReplEcommStoreTenderTypesResult;
    }

    /**
     * @return ReplStoreTenderTypeResponse
     */
    public function getResult()
    {
        return $this->ReplEcommStoreTenderTypesResult;
    }
}

