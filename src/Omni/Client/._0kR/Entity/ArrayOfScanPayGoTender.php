<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfScanPayGoTender implements IteratorAggregate
{
    /**
     * @property ScanPayGoTender[] $ScanPayGoTender
     */
    protected $ScanPayGoTender = [
        
    ];

    /**
     * @param ScanPayGoTender[] $ScanPayGoTender
     * @return $this
     */
    public function setScanPayGoTender($ScanPayGoTender)
    {
        $this->ScanPayGoTender = $ScanPayGoTender;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ScanPayGoTender );
    }

    /**
     * @return ScanPayGoTender[]
     */
    public function getScanPayGoTender()
    {
        return $this->ScanPayGoTender;
    }
}
