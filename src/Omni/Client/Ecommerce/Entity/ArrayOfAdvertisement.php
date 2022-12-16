<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfAdvertisement implements IteratorAggregate
{

    /**
     * @property Advertisement[] $Advertisement
     */
    protected $Advertisement = [
        
    ];

    /**
     * @param Advertisement[] $Advertisement
     * @return $this
     */
    public function setAdvertisement($Advertisement)
    {
        $this->Advertisement = $Advertisement;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->Advertisement );
    }

    /**
     * @return Advertisement[]
     */
    public function getAdvertisement()
    {
        return $this->Advertisement;
    }


}

