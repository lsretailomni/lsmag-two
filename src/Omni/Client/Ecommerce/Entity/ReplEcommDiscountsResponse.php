<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommDiscountsResponse implements ResponseInterface
{
    /**
     * @property ReplDiscountResponse $ReplEcommDiscountsResult
     */
    protected $ReplEcommDiscountsResult = null;

    /**
     * @param ReplDiscountResponse $ReplEcommDiscountsResult
     * @return $this
     */
    public function setReplEcommDiscountsResult($ReplEcommDiscountsResult)
    {
        $this->ReplEcommDiscountsResult = $ReplEcommDiscountsResult;
        return $this;
    }

    /**
     * @return ReplDiscountResponse
     */
    public function getReplEcommDiscountsResult()
    {
        return $this->ReplEcommDiscountsResult;
    }

    /**
     * @return ReplDiscountResponse
     */
    public function getResult()
    {
        return $this->ReplEcommDiscountsResult;
    }
}

