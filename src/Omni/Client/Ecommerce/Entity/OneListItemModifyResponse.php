<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class OneListItemModifyResponse implements ResponseInterface
{
    /**
     * @property OneList $OneListItemModifyResult
     */
    protected $OneListItemModifyResult = null;

    /**
     * @param OneList $OneListItemModifyResult
     * @return $this
     */
    public function setOneListItemModifyResult($OneListItemModifyResult)
    {
        $this->OneListItemModifyResult = $OneListItemModifyResult;
        return $this;
    }

    /**
     * @return OneList
     */
    public function getOneListItemModifyResult()
    {
        return $this->OneListItemModifyResult;
    }

    /**
     * @return OneList
     */
    public function getResult()
    {
        return $this->OneListItemModifyResult;
    }
}

