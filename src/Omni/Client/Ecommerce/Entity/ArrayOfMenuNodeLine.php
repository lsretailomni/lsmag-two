<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfMenuNodeLine implements IteratorAggregate
{

    /**
     * @property MenuNodeLine[] $MenuNodeLine
     */
    protected $MenuNodeLine = [
        
    ];

    /**
     * @param MenuNodeLine[] $MenuNodeLine
     * @return $this
     */
    public function setMenuNodeLine($MenuNodeLine)
    {
        $this->MenuNodeLine = $MenuNodeLine;
        return $this;
    }

    /**
     * @return MenuNodeLine[]
     */
    public function getIterator()
    {
        return new ArrayIterator( $this->MenuNodeLine );
    }

    /**
     * @return MenuNodeLine[]
     */
    public function getMenuNodeLine()
    {
        return $this->MenuNodeLine;
    }


}
