<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfTextModifier implements IteratorAggregate
{

    /**
     * @property TextModifier[] $TextModifier
     */
    protected $TextModifier = array(
        
    );

    /**
     * @param TextModifier[] $TextModifier
     * @return $this
     */
    public function setTextModifier($TextModifier)
    {
        $this->TextModifier = $TextModifier;
        return $this;
    }

    /**
     * @return TextModifier[]
     */
    public function getIterator()
    {
        return new ArrayIterator( $this->TextModifier );
    }

    /**
     * @return TextModifier[]
     */
    public function getTextModifier()
    {
        return $this->TextModifier;
    }


}

