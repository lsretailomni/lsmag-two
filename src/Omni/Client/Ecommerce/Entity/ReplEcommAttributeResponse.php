<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommAttributeResponse implements ResponseInterface
{
    /**
     * @property ReplAttributeResponse $ReplEcommAttributeResult
     */
    protected $ReplEcommAttributeResult = null;

    /**
     * @param ReplAttributeResponse $ReplEcommAttributeResult
     * @return $this
     */
    public function setReplEcommAttributeResult($ReplEcommAttributeResult)
    {
        $this->ReplEcommAttributeResult = $ReplEcommAttributeResult;
        return $this;
    }

    /**
     * @return ReplAttributeResponse
     */
    public function getReplEcommAttributeResult()
    {
        return $this->ReplEcommAttributeResult;
    }

    /**
     * @return ReplAttributeResponse
     */
    public function getResult()
    {
        return $this->ReplEcommAttributeResult;
    }
}

