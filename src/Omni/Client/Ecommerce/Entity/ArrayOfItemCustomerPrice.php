<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfItemCustomerPrice implements IteratorAggregate
{
    /**
     * @property ItemCustomerPrice[] $ItemCustomerPrice
     */
    protected $ItemCustomerPrice = [
        
    ];

    /**
     * @param ItemCustomerPrice[] $ItemCustomerPrice
     * @return $this
     */
    public function setItemCustomerPrice($ItemCustomerPrice)
    {
        $this->ItemCustomerPrice = $ItemCustomerPrice;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ItemCustomerPrice );
    }

    /**
     * @return ItemCustomerPrice[]
     */
    public function getItemCustomerPrice()
    {
        return $this->ItemCustomerPrice;
    }
}

