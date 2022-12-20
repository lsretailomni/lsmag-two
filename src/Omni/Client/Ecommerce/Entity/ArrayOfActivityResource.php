<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfActivityResource implements IteratorAggregate
{

    /**
     * @property ActivityResource[] $ActivityResource
     */
    protected $ActivityResource = [
        
    ];

    /**
     * @param ActivityResource[] $ActivityResource
     * @return $this
     */
    public function setActivityResource($ActivityResource)
    {
        $this->ActivityResource = $ActivityResource;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ActivityResource );
    }

    /**
     * @return ActivityResource[]
     */
    public function getActivityResource()
    {
        return $this->ActivityResource;
    }


}

