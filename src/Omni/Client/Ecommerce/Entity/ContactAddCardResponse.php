<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ContactAddCardResponse implements ResponseInterface
{
    /**
     * @property double $ContactAddCardResult
     */
    protected $ContactAddCardResult = null;

    /**
     * @param double $ContactAddCardResult
     * @return $this
     */
    public function setContactAddCardResult($ContactAddCardResult)
    {
        $this->ContactAddCardResult = $ContactAddCardResult;
        return $this;
    }

    /**
     * @return double
     */
    public function getContactAddCardResult()
    {
        return $this->ContactAddCardResult;
    }

    /**
     * @return double
     */
    public function getResult()
    {
        return $this->ContactAddCardResult;
    }
}

