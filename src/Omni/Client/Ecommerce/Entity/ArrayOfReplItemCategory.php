<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfReplItemCategory implements IteratorAggregate
{

    /**
     * @property ReplItemCategory[] $ReplItemCategory
     */
    protected $ReplItemCategory = [
        
    ];

    /**
     * @param ReplItemCategory[] $ReplItemCategory
     * @return $this
     */
    public function setReplItemCategory($ReplItemCategory)
    {
        $this->ReplItemCategory = $ReplItemCategory;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->ReplItemCategory );
    }

    /**
     * @return ReplItemCategory[]
     */
    public function getReplItemCategory()
    {
        return $this->ReplItemCategory;
    }


}

