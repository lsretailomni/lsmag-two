<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfLoyItem implements IteratorAggregate
{
    /**
     * @property LoyItem[] $LoyItem
     */
    protected $LoyItem = [
        
    ];

    /**
     * @param LoyItem[] $LoyItem
     * @return $this
     */
    public function setLoyItem($LoyItem)
    {
        $this->LoyItem = $LoyItem;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->LoyItem );
    }

    /**
     * @return LoyItem[]
     */
    public function getLoyItem()
    {
        return $this->LoyItem;
    }
}

