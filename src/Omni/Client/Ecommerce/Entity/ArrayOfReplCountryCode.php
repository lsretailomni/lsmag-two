<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfReplCountryCode implements IteratorAggregate
{
    /**
     * @property ReplCountryCode[] $ReplCountryCode
     */
    protected $ReplCountryCode = [
        
    ];

    /**
     * @param ReplCountryCode[] $ReplCountryCode
     * @return $this
     */
    public function setReplCountryCode($ReplCountryCode)
    {
        $this->ReplCountryCode = $ReplCountryCode;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ReplCountryCode );
    }

    /**
     * @return ReplCountryCode[]
     */
    public function getReplCountryCode()
    {
        return $this->ReplCountryCode;
    }
}

