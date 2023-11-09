<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ItemsGetByPublishedOfferId implements RequestInterface
{
    /**
     * @property string $pubOfferId
     */
    protected $pubOfferId = null;

    /**
     * @property int $numberOfItems
     */
    protected $numberOfItems = null;

    /**
     * @param string $pubOfferId
     * @return $this
     */
    public function setPubOfferId($pubOfferId)
    {
        $this->pubOfferId = $pubOfferId;
        return $this;
    }

    /**
     * @return string
     */
    public function getPubOfferId()
    {
        return $this->pubOfferId;
    }

    /**
     * @param int $numberOfItems
     * @return $this
     */
    public function setNumberOfItems($numberOfItems)
    {
        $this->numberOfItems = $numberOfItems;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfItems()
    {
        return $this->numberOfItems;
    }
}

