<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class MenuDeal extends MenuItem
{
    /**
     * @property ArrayOfMenuDealLine $DealLines
     */
    protected $DealLines = null;

    /**
     * @param ArrayOfMenuDealLine $DealLines
     * @return $this
     */
    public function setDealLines($DealLines)
    {
        $this->DealLines = $DealLines;
        return $this;
    }

    /**
     * @return ArrayOfMenuDealLine
     */
    public function getDealLines()
    {
        return $this->DealLines;
    }
}

