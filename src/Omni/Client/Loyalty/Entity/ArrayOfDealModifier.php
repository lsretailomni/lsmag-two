<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 */


namespace Ls\Omni\Client\Loyalty\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfDealModifier implements IteratorAggregate
{

    /**
     * @property DealModifier[] $DealModifier
     */
    protected $DealModifier = array(
        
    );

    /**
     * @param DealModifier[] $DealModifier
     * @return $this
     */
    public function setDealModifier($DealModifier)
    {
        $this->DealModifier = $DealModifier;
        return $this;
    }

    /**
     * @return DealModifier[]
     */
    public function getIterator()
    {
        return new ArrayIterator( $this->DealModifier );
    }

    /**
     * @return DealModifier[]
     */
    public function getDealModifier()
    {
        return $this->DealModifier;
    }


}

