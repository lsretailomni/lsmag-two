<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfAvailabilityResponse implements IteratorAggregate
{
    /**
     * @property AvailabilityResponse[] $AvailabilityResponse
     */
    protected $AvailabilityResponse = [
        
    ];

    /**
     * @param AvailabilityResponse[] $AvailabilityResponse
     * @return $this
     */
    public function setAvailabilityResponse($AvailabilityResponse)
    {
        $this->AvailabilityResponse = $AvailabilityResponse;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->AvailabilityResponse );
    }

    /**
     * @return AvailabilityResponse[]
     */
    public function getAvailabilityResponse()
    {
        return $this->AvailabilityResponse;
    }
}

