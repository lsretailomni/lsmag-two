<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class CardGetPointEntiesResponse implements ResponseInterface
{

    /**
     * @property ArrayOfPointEntry $CardGetPointEntiesResult
     */
    protected $CardGetPointEntiesResult = null;

    /**
     * @param ArrayOfPointEntry $CardGetPointEntiesResult
     * @return $this
     */
    public function setCardGetPointEntiesResult($CardGetPointEntiesResult)
    {
        $this->CardGetPointEntiesResult = $CardGetPointEntiesResult;
        return $this;
    }

    /**
     * @return ArrayOfPointEntry
     */
    public function getCardGetPointEntiesResult()
    {
        return $this->CardGetPointEntiesResult;
    }

    /**
     * @return ArrayOfPointEntry
     */
    public function getResult()
    {
        return $this->CardGetPointEntiesResult;
    }


}

