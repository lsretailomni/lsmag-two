<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfRetailAttribute implements IteratorAggregate
{

    /**
     * @property RetailAttribute[] $RetailAttribute
     */
    protected $RetailAttribute = array(
        
    );

    /**
     * @param RetailAttribute[] $RetailAttribute
     * @return $this
     */
    public function setRetailAttribute($RetailAttribute)
    {
        $this->RetailAttribute = $RetailAttribute;
        return $this;
    }

    /**
     * @return RetailAttribute[]
     */
    public function getIterator()
    {
        return new ArrayIterator( $this->RetailAttribute );
    }

    /**
     * @return RetailAttribute[]
     */
    public function getRetailAttribute()
    {
        return $this->RetailAttribute;
    }


}

