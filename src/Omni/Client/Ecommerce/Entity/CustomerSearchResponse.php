<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class CustomerSearchResponse implements ResponseInterface
{

    /**
     * @property ArrayOfCustomer $CustomerSearchResult
     */
    protected $CustomerSearchResult = null;

    /**
     * @param ArrayOfCustomer $CustomerSearchResult
     * @return $this
     */
    public function setCustomerSearchResult($CustomerSearchResult)
    {
        $this->CustomerSearchResult = $CustomerSearchResult;
        return $this;
    }

    /**
     * @return ArrayOfCustomer
     */
    public function getCustomerSearchResult()
    {
        return $this->CustomerSearchResult;
    }

    /**
     * @return ArrayOfCustomer
     */
    public function getResult()
    {
        return $this->CustomerSearchResult;
    }


}

