<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommItemVariantsResponse implements ResponseInterface
{

    /**
     * @property ReplItemVariantResponse $ReplEcommItemVariantsResult
     */
    protected $ReplEcommItemVariantsResult = null;

    /**
     * @param ReplItemVariantResponse $ReplEcommItemVariantsResult
     * @return $this
     */
    public function setReplEcommItemVariantsResult($ReplEcommItemVariantsResult)
    {
        $this->ReplEcommItemVariantsResult = $ReplEcommItemVariantsResult;
        return $this;
    }

    /**
     * @return ReplItemVariantResponse
     */
    public function getReplEcommItemVariantsResult()
    {
        return $this->ReplEcommItemVariantsResult;
    }

    /**
     * @return ReplItemVariantResponse
     */
    public function getResult()
    {
        return $this->ReplEcommItemVariantsResult;
    }


}

