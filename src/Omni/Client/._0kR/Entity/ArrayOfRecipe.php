<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfRecipe implements IteratorAggregate
{
    /**
     * @property Recipe[] $Recipe
     */
    protected $Recipe = [
        
    ];

    /**
     * @param Recipe[] $Recipe
     * @return $this
     */
    public function setRecipe($Recipe)
    {
        $this->Recipe = $Recipe;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator() : \Traversable
    {
        return new ArrayIterator( $this->Recipe );
    }

    /**
     * @return Recipe[]
     */
    public function getRecipe()
    {
        return $this->Recipe;
    }
}
