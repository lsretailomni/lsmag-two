<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfActivityLocation implements IteratorAggregate
{
    /**
     * @property ActivityLocation[] $ActivityLocation
     */
    protected $ActivityLocation = [
        
    ];

    /**
     * @param ActivityLocation[] $ActivityLocation
     * @return $this
     */
    public function setActivityLocation($ActivityLocation)
    {
        $this->ActivityLocation = $ActivityLocation;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ActivityLocation );
    }

    /**
     * @return ActivityLocation[]
     */
    public function getActivityLocation()
    {
        return $this->ActivityLocation;
    }
}

