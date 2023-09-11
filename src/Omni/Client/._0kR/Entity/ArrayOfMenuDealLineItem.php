<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfMenuDealLineItem implements IteratorAggregate
{
    /**
     * @property MenuDealLineItem[] $MenuDealLineItem
     */
    protected $MenuDealLineItem = [
        
    ];

    /**
     * @param MenuDealLineItem[] $MenuDealLineItem
     * @return $this
     */
    public function setMenuDealLineItem($MenuDealLineItem)
    {
        $this->MenuDealLineItem = $MenuDealLineItem;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->MenuDealLineItem );
    }

    /**
     * @return MenuDealLineItem[]
     */
    public function getMenuDealLineItem()
    {
        return $this->MenuDealLineItem;
    }
}

