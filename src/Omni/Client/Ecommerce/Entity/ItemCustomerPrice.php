<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ItemCustomerPrice extends Entity
{
    /**
     * @property float $DiscountPercent
     */
    protected $DiscountPercent = null;

    /**
     * @property int $LineNo
     */
    protected $LineNo = null;

    /**
     * @property float $Price
     */
    protected $Price = null;

    /**
     * @property float $Quantity
     */
    protected $Quantity = null;

    /**
     * @property string $VariantId
     */
    protected $VariantId = null;

    /**
     * @param float $DiscountPercent
     * @return $this
     */
    public function setDiscountPercent($DiscountPercent)
    {
        $this->DiscountPercent = $DiscountPercent;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountPercent()
    {
        return $this->DiscountPercent;
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
     * @param float $Price
     * @return $this
     */
    public function setPrice($Price)
    {
        $this->Price = $Price;
        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->Price;
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

    /**
     * @param string $VariantId
     * @return $this
     */
    public function setVariantId($VariantId)
    {
        $this->VariantId = $VariantId;
        return $this;
    }

    /**
     * @return string
     */
    public function getVariantId()
    {
        return $this->VariantId;
    }
}

