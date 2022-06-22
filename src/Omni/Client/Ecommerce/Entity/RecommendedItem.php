<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class RecommendedItem
{

    /**
     * @property string $itemNo
     */
    protected $itemNo = null;

    /**
     * @property float $lift
     */
    protected $lift = null;

    /**
     * @param string $itemNo
     * @return $this
     */
    public function setItemNo($itemNo)
    {
        $this->itemNo = $itemNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemNo()
    {
        return $this->itemNo;
    }

    /**
     * @param float $lift
     * @return $this
     */
    public function setLift($lift)
    {
        $this->lift = $lift;
        return $this;
    }

    /**
     * @return float
     */
    public function getLift()
    {
        return $this->lift;
    }


}

