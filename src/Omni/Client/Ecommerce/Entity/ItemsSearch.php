<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ItemsSearch implements RequestInterface
{
    /**
     * @property string $search
     */
    protected $search = null;

    /**
     * @property int $maxNumberOfItems
     */
    protected $maxNumberOfItems = null;

    /**
     * @property boolean $includeDetails
     */
    protected $includeDetails = null;

    /**
     * @param string $search
     * @return $this
     */
    public function setSearch($search)
    {
        $this->search = $search;
        return $this;
    }

    /**
     * @return string
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @param int $maxNumberOfItems
     * @return $this
     */
    public function setMaxNumberOfItems($maxNumberOfItems)
    {
        $this->maxNumberOfItems = $maxNumberOfItems;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxNumberOfItems()
    {
        return $this->maxNumberOfItems;
    }

    /**
     * @param boolean $includeDetails
     * @return $this
     */
    public function setIncludeDetails($includeDetails)
    {
        $this->includeDetails = $includeDetails;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIncludeDetails()
    {
        return $this->includeDetails;
    }
}

