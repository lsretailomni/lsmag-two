<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfReplItemModifier implements IteratorAggregate
{

    /**
     * @property ReplItemModifier[] $ReplItemModifier
     */
    protected $ReplItemModifier = [

    ];

    /**
     * @param ReplItemModifier[] $ReplItemModifier
     * @return $this
     */
    public function setReplItemModifier($ReplItemModifier)
    {
        $this->ReplItemModifier = $ReplItemModifier;
        return $this;
    }

    /**
     * @return ReplItemModifier[]
     */
    public function getIterator()
    {
        return new ArrayIterator( $this->ReplItemModifier );
    }

    /**
     * @return ReplItemModifier[]
     */
    public function getReplItemModifier()
    {
        return $this->ReplItemModifier;
    }


}

