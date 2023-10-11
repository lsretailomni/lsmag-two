<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class OrderCancelLine
{
    /**
     * @property string $ItemNo
     */
    protected $ItemNo = null;

    /**
     * @property int $LineNo
     */
    protected $LineNo = null;

    /**
     * @property float $Quantity
     */
    protected $Quantity = null;

    /**
     * @param string $ItemNo
     * @return $this
     */
    public function setItemNo($ItemNo)
    {
        $this->ItemNo = $ItemNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemNo()
    {
        return $this->ItemNo;
    }

    /**
     * @param int $LineNo
     * @return $this
     */
    public function setLineNo($LineNo)
    {
        $this->LineNo = $LineNo;
        return $this;
    }

    /**
     * @return int
     */
    public function getLineNo()
    {
        return $this->LineNo;
    }

    /**
     * @param float $Quantity
     * @return $this
     */
    public function setQuantity($Quantity)
    {
        $this->Quantity = $Quantity;
        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->Quantity;
    }
}

