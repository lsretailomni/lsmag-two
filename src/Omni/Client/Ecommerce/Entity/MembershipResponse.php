<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class MembershipResponse extends Entity
{

    /**
     * @property string $BookingRef
     */
    protected $BookingRef = null;

    /**
     * @property float $Discount
     */
    protected $Discount = null;

    /**
     * @property string $ItemNo
     */
    protected $ItemNo = null;

    /**
     * @property float $Price
     */
    protected $Price = null;

    /**
     * @property float $Quantity
     */
    protected $Quantity = null;

    /**
     * @param string $BookingRef
     * @return $this
     */
    public function setBookingRef($BookingRef)
    {
        $this->BookingRef = $BookingRef;
        return $this;
    }

    /**
     * @return string
     */
    public function getBookingRef()
    {
        return $this->BookingRef;
    }

    /**
     * @param float $Discount
     * @return $this
     */
    public function setDiscount($Discount)
    {
        $this->Discount = $Discount;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscount()
    {
        return $this->Discount;
    }

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


}

