<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ReplEcommTaxSetupResponse implements ResponseInterface
{
    /**
     * @property ReplTaxSetupResponse $ReplEcommTaxSetupResult
     */
    protected $ReplEcommTaxSetupResult = null;

    /**
     * @param ReplTaxSetupResponse $ReplEcommTaxSetupResult
     * @return $this
     */
    public function setReplEcommTaxSetupResult($ReplEcommTaxSetupResult)
    {
        $this->ReplEcommTaxSetupResult = $ReplEcommTaxSetupResult;
        return $this;
    }

    /**
     * @return ReplTaxSetupResponse
     */
    public function getReplEcommTaxSetupResult()
    {
        return $this->ReplEcommTaxSetupResult;
    }

    /**
     * @return ReplTaxSetupResponse
     */
    public function getResult()
    {
        return $this->ReplEcommTaxSetupResult;
    }
}

