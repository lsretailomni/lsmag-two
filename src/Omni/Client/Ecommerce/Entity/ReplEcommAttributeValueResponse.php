<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommAttributeValueResponse implements ResponseInterface
{
    /**
     * @property ReplAttributeValueResponse $ReplEcommAttributeValueResult
     */
    protected $ReplEcommAttributeValueResult = null;

    /**
     * @param ReplAttributeValueResponse $ReplEcommAttributeValueResult
     * @return $this
     */
    public function setReplEcommAttributeValueResult($ReplEcommAttributeValueResult)
    {
        $this->ReplEcommAttributeValueResult = $ReplEcommAttributeValueResult;
        return $this;
    }

    /**
     * @return ReplAttributeValueResponse
     */
    public function getReplEcommAttributeValueResult()
    {
        return $this->ReplEcommAttributeValueResult;
    }

    /**
     * @return ReplAttributeValueResponse
     */
    public function getResult()
    {
        return $this->ReplEcommAttributeValueResult;
    }
}

