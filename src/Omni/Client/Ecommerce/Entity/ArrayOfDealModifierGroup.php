<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfDealModifierGroup implements IteratorAggregate
{
    /**
     * @property DealModifierGroup[] $DealModifierGroup
     */
    protected $DealModifierGroup = [
        
    ];

    /**
     * @param DealModifierGroup[] $DealModifierGroup
     * @return $this
     */
    public function setDealModifierGroup($DealModifierGroup)
    {
        $this->DealModifierGroup = $DealModifierGroup;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->DealModifierGroup );
    }

    /**
     * @return DealModifierGroup[]
     */
    public function getDealModifierGroup()
    {
        return $this->DealModifierGroup;
    }
}

