<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommMemberResponse implements ResponseInterface
{
    /**
     * @property ReplCustomerResponse $ReplEcommMemberResult
     */
    protected $ReplEcommMemberResult = null;

    /**
     * @param ReplCustomerResponse $ReplEcommMemberResult
     * @return $this
     */
    public function setReplEcommMemberResult($ReplEcommMemberResult)
    {
        $this->ReplEcommMemberResult = $ReplEcommMemberResult;
        return $this;
    }

    /**
     * @return ReplCustomerResponse
     */
    public function getReplEcommMemberResult()
    {
        return $this->ReplEcommMemberResult;
    }

    /**
     * @return ReplCustomerResponse
     */
    public function getResult()
    {
        return $this->ReplEcommMemberResult;
    }
}

