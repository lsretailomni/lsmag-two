<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommHierarchyHospDealResponse implements ResponseInterface
{
    /**
     * @property ReplHierarchyHospDealResponse $ReplEcommHierarchyHospDealResult
     */
    protected $ReplEcommHierarchyHospDealResult = null;

    /**
     * @param ReplHierarchyHospDealResponse $ReplEcommHierarchyHospDealResult
     * @return $this
     */
    public function setReplEcommHierarchyHospDealResult($ReplEcommHierarchyHospDealResult)
    {
        $this->ReplEcommHierarchyHospDealResult = $ReplEcommHierarchyHospDealResult;
        return $this;
    }

    /**
     * @return ReplHierarchyHospDealResponse
     */
    public function getReplEcommHierarchyHospDealResult()
    {
        return $this->ReplEcommHierarchyHospDealResult;
    }

    /**
     * @return ReplHierarchyHospDealResponse
     */
    public function getResult()
    {
        return $this->ReplEcommHierarchyHospDealResult;
    }
}
