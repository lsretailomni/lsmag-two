<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommHierarchyLeafResponse implements ResponseInterface
{
    /**
     * @property ReplHierarchyLeafResponse $ReplEcommHierarchyLeafResult
     */
    protected $ReplEcommHierarchyLeafResult = null;

    /**
     * @param ReplHierarchyLeafResponse $ReplEcommHierarchyLeafResult
     * @return $this
     */
    public function setReplEcommHierarchyLeafResult($ReplEcommHierarchyLeafResult)
    {
        $this->ReplEcommHierarchyLeafResult = $ReplEcommHierarchyLeafResult;
        return $this;
    }

    /**
     * @return ReplHierarchyLeafResponse
     */
    public function getReplEcommHierarchyLeafResult()
    {
        return $this->ReplEcommHierarchyLeafResult;
    }

    /**
     * @return ReplHierarchyLeafResponse
     */
    public function getResult()
    {
        return $this->ReplEcommHierarchyLeafResult;
    }
}

