<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use IteratorAggregate;
use ArrayIterator;

class ArrayOfLoyTransactionHeader implements IteratorAggregate
{

    /**
     * @property LoyTransactionHeader[] $LoyTransactionHeader
     */
    protected $LoyTransactionHeader = array(
        
    );

    /**
     * @param LoyTransactionHeader[] $LoyTransactionHeader
     * @return $this
     */
    public function setLoyTransactionHeader($LoyTransactionHeader)
    {
        $this->LoyTransactionHeader = $LoyTransactionHeader;
        return $this;
    }

    /**
     * @return LoyTransactionHeader[]
     */
    public function getIterator()
    {
        return new ArrayIterator( $this->LoyTransactionHeader );
    }

    /**
     * @return LoyTransactionHeader[]
     */
    public function getLoyTransactionHeader()
    {
        return $this->LoyTransactionHeader;
    }


}
