<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfSalesEntryPayment implements IteratorAggregate
{

    /**
     * @property SalesEntryPayment[] $SalesEntryPayment
     */
    protected $SalesEntryPayment = array(
        
    );

    /**
     * @param SalesEntryPayment[] $SalesEntryPayment
     * @return $this
     */
    public function setSalesEntryPayment($SalesEntryPayment)
    {
        $this->SalesEntryPayment = $SalesEntryPayment;
        return $this;
    }

    /**
     * @return SalesEntryPayment[]
     */
    public function getIterator()
    {
        return new ArrayIterator( $this->SalesEntryPayment );
    }

    /**
     * @return SalesEntryPayment[]
     */
    public function getSalesEntryPayment()
    {
        return $this->SalesEntryPayment;
    }


}

