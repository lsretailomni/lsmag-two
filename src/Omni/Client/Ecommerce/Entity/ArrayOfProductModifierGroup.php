<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfProductModifierGroup implements IteratorAggregate
{
    /**
     * @property ProductModifierGroup[] $ProductModifierGroup
     */
    protected $ProductModifierGroup = [
        
    ];

    /**
     * @param ProductModifierGroup[] $ProductModifierGroup
     * @return $this
     */
    public function setProductModifierGroup($ProductModifierGroup)
    {
        $this->ProductModifierGroup = $ProductModifierGroup;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ProductModifierGroup );
    }

    /**
     * @return ProductModifierGroup[]
     */
    public function getProductModifierGroup()
    {
        return $this->ProductModifierGroup;
    }
}

