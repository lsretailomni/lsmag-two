<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommItemModifierResponse implements ResponseInterface
{

    /**
     * @property ReplItemModifierResponse $ReplEcommItemModifierResult
     */
    protected $ReplEcommItemModifierResult = null;

    /**
     * @param ReplItemModifierResponse $ReplEcommItemModifierResult
     * @return $this
     */
    public function setReplEcommItemModifierResult($ReplEcommItemModifierResult)
    {
        $this->ReplEcommItemModifierResult = $ReplEcommItemModifierResult;
        return $this;
    }

    /**
     * @return ReplItemModifierResponse
     */
    public function getReplEcommItemModifierResult()
    {
        return $this->ReplEcommItemModifierResult;
    }

    /**
     * @return ReplItemModifierResponse
     */
    public function getResult()
    {
        return $this->ReplEcommItemModifierResult;
    }


}
