<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommUnitOfMeasuresResponse implements ResponseInterface
{
    /**
     * @property ReplUnitOfMeasureResponse $ReplEcommUnitOfMeasuresResult
     */
    protected $ReplEcommUnitOfMeasuresResult = null;

    /**
     * @param ReplUnitOfMeasureResponse $ReplEcommUnitOfMeasuresResult
     * @return $this
     */
    public function setReplEcommUnitOfMeasuresResult($ReplEcommUnitOfMeasuresResult)
    {
        $this->ReplEcommUnitOfMeasuresResult = $ReplEcommUnitOfMeasuresResult;
        return $this;
    }

    /**
     * @return ReplUnitOfMeasureResponse
     */
    public function getReplEcommUnitOfMeasuresResult()
    {
        return $this->ReplEcommUnitOfMeasuresResult;
    }

    /**
     * @return ReplUnitOfMeasureResponse
     */
    public function getResult()
    {
        return $this->ReplEcommUnitOfMeasuresResult;
    }
}

