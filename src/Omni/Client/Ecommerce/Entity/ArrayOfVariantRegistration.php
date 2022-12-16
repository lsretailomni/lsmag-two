<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfVariantRegistration implements IteratorAggregate
{

    /**
     * @property VariantRegistration[] $VariantRegistration
     */
    protected $VariantRegistration = [
        
    ];

    /**
     * @param VariantRegistration[] $VariantRegistration
     * @return $this
     */
    public function setVariantRegistration($VariantRegistration)
    {
        $this->VariantRegistration = $VariantRegistration;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->VariantRegistration );
    }

    /**
     * @return VariantRegistration[]
     */
    public function getVariantRegistration()
    {
        return $this->VariantRegistration;
    }


}

