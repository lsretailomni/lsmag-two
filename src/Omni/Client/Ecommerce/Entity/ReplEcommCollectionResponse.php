<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommCollectionResponse implements ResponseInterface
{
    /**
     * @property ReplCollectionResponse $ReplEcommCollectionResult
     */
    protected $ReplEcommCollectionResult = null;

    /**
     * @param ReplCollectionResponse $ReplEcommCollectionResult
     * @return $this
     */
    public function setReplEcommCollectionResult($ReplEcommCollectionResult)
    {
        $this->ReplEcommCollectionResult = $ReplEcommCollectionResult;
        return $this;
    }

    /**
     * @return ReplCollectionResponse
     */
    public function getReplEcommCollectionResult()
    {
        return $this->ReplEcommCollectionResult;
    }

    /**
     * @return ReplCollectionResponse
     */
    public function getResult()
    {
        return $this->ReplEcommCollectionResult;
    }
}

