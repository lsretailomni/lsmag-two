<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfReplImageLink implements IteratorAggregate
{
    /**
     * @property ReplImageLink[] $ReplImageLink
     */
    protected $ReplImageLink = [
        
    ];

    /**
     * @param ReplImageLink[] $ReplImageLink
     * @return $this
     */
    public function setReplImageLink($ReplImageLink)
    {
        $this->ReplImageLink = $ReplImageLink;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ReplImageLink );
    }

    /**
     * @return ReplImageLink[]
     */
    public function getReplImageLink()
    {
        return $this->ReplImageLink;
    }
}

