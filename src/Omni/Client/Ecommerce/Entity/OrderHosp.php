<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\HospDeliveryType;
use Ls\Omni\Client\Ecommerce\Entity\Enum\HospPaymentType;
use Ls\Omni\Exception\InvalidEnumException;

class OrderHosp extends Entity
{

    /**
     * @property ArrayOfOrderHospLine $OrderLines
     */
    protected $OrderLines = null;

    /**
     * @property ArrayOfOrderPayment $OrderPayments
     */
    protected $OrderPayments = null;

    /**
     * @property Address $Address
     */
    protected $Address = null;

    /**
     * @property string $BillToName
     */
    protected $BillToName = null;

    /**
     * @property string $CardId
     */
    protected $CardId = null;

    /**
     * @property HospDeliveryType $DeliveryType
     */
    protected $DeliveryType = null;

    /**
     * @property string $Directions
     */
    protected $Directions = null;

    /**
     * @property string $DocumentId
     */
    protected $DocumentId = null;

    /**
     * @property string $DocumentRegTime
     */
    protected $DocumentRegTime = null;

    /**
     * @property string $Email
     */
    protected $Email = null;

    /**
     * @property int $LineItemCount
     */
    protected $LineItemCount = null;

    /**
     * @property string $Name
     */
    protected $Name = null;

    /**
     * @property string $OrderDate
     */
    protected $OrderDate = null;

    /**
     * @property HospPaymentType $PaymentType
     */
    protected $PaymentType = null;

    /**
     * @property string $PickupTime
     */
    protected $PickupTime = null;

    /**
     * @property string $ReceiptNo
     */
    protected $ReceiptNo = null;

    /**
     * @property string $RestaurantNo
     */
    protected $RestaurantNo = null;

    /**
     * @property string $StoreId
     */
    protected $StoreId = null;

    /**
     * @property float $TotalAmount
     */
    protected $TotalAmount = null;

    /**
     * @property float $TotalDiscount
     */
    protected $TotalDiscount = null;

    /**
     * @property float $TotalNetAmount
     */
    protected $TotalNetAmount = null;

    /**
     * @param ArrayOfOrderHospLine $OrderLines
     * @return $this
     */
    public function setOrderLines($OrderLines)
    {
        $this->OrderLines = $OrderLines;
        return $this;
    }

    /**
     * @return ArrayOfOrderHospLine
     */
    public function getOrderLines()
    {
        return $this->OrderLines;
    }

    /**
     * @param ArrayOfOrderPayment $OrderPayments
     * @return $this
     */
    public function setOrderPayments($OrderPayments)
    {
        $this->OrderPayments = $OrderPayments;
        return $this;
    }

    /**
     * @return ArrayOfOrderPayment
     */
    public function getOrderPayments()
    {
        return $this->OrderPayments;
    }

    /**
     * @param Address $Address
     * @return $this
     */
    public function setAddress($Address)
    {
        $this->Address = $Address;
        return $this;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->Address;
    }

    /**
     * @param string $BillToName
     * @return $this
     */
    public function setBillToName($BillToName)
    {
        $this->BillToName = $BillToName;
        return $this;
    }

    /**
     * @return string
     */
    public function getBillToName()
    {
        return $this->BillToName;
    }

    /**
     * @param string $CardId
     * @return $this
     */
    public function setCardId($CardId)
    {
        $this->CardId = $CardId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardId()
    {
        return $this->CardId;
    }

    /**
     * @param HospDeliveryType|string $DeliveryType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setDeliveryType($DeliveryType)
    {
        if ( ! $DeliveryType instanceof HospDeliveryType ) {
            if ( HospDeliveryType::isValid( $DeliveryType ) )
                $DeliveryType = new HospDeliveryType( $DeliveryType );
            elseif ( HospDeliveryType::isValidKey( $DeliveryType ) )
                $DeliveryType = new HospDeliveryType( constant( "HospDeliveryType::$DeliveryType" ) );
            elseif ( ! $DeliveryType instanceof HospDeliveryType )
                throw new InvalidEnumException();
        }
        $this->DeliveryType = $DeliveryType->getValue();

        return $this;
    }

    /**
     * @return HospDeliveryType
     */
    public function getDeliveryType()
    {
        return $this->DeliveryType;
    }

    /**
     * @param string $Directions
     * @return $this
     */
    public function setDirections($Directions)
    {
        $this->Directions = $Directions;
        return $this;
    }

    /**
     * @return string
     */
    public function getDirections()
    {
        return $this->Directions;
    }

    /**
     * @param string $DocumentId
     * @return $this
     */
    public function setDocumentId($DocumentId)
    {
        $this->DocumentId = $DocumentId;
        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentId()
    {
        return $this->DocumentId;
    }

    /**
     * @param string $DocumentRegTime
     * @return $this
     */
    public function setDocumentRegTime($DocumentRegTime)
    {
        $this->DocumentRegTime = $DocumentRegTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentRegTime()
    {
        return $this->DocumentRegTime;
    }

    /**
     * @param string $Email
     * @return $this
     */
    public function setEmail($Email)
    {
        $this->Email = $Email;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->Email;
    }

    /**
     * @param int $LineItemCount
     * @return $this
     */
    public function setLineItemCount($LineItemCount)
    {
        $this->LineItemCount = $LineItemCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getLineItemCount()
    {
        return $this->LineItemCount;
    }

    /**
     * @param string $Name
     * @return $this
     */
    public function setName($Name)
    {
        $this->Name = $Name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * @param string $OrderDate
     * @return $this
     */
    public function setOrderDate($OrderDate)
    {
        $this->OrderDate = $OrderDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderDate()
    {
        return $this->OrderDate;
    }

    /**
     * @param HospPaymentType|string $PaymentType
     * @return $this
     * @throws InvalidEnumException
     */
    public function setPaymentType($PaymentType)
    {
        if ( ! $PaymentType instanceof HospPaymentType ) {
            if ( HospPaymentType::isValid( $PaymentType ) )
                $PaymentType = new HospPaymentType( $PaymentType );
            elseif ( HospPaymentType::isValidKey( $PaymentType ) )
                $PaymentType = new HospPaymentType( constant( "HospPaymentType::$PaymentType" ) );
            elseif ( ! $PaymentType instanceof HospPaymentType )
                throw new InvalidEnumException();
        }
        $this->PaymentType = $PaymentType->getValue();

        return $this;
    }

    /**
     * @return HospPaymentType
     */
    public function getPaymentType()
    {
        return $this->PaymentType;
    }

    /**
     * @param string $PickupTime
     * @return $this
     */
    public function setPickupTime($PickupTime)
    {
        $this->PickupTime = $PickupTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getPickupTime()
    {
        return $this->PickupTime;
    }

    /**
     * @param string $ReceiptNo
     * @return $this
     */
    public function setReceiptNo($ReceiptNo)
    {
        $this->ReceiptNo = $ReceiptNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiptNo()
    {
        return $this->ReceiptNo;
    }

    /**
     * @param string $RestaurantNo
     * @return $this
     */
    public function setRestaurantNo($RestaurantNo)
    {
        $this->RestaurantNo = $RestaurantNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getRestaurantNo()
    {
        return $this->RestaurantNo;
    }

    /**
     * @param string $StoreId
     * @return $this
     */
    public function setStoreId($StoreId)
    {
        $this->StoreId = $StoreId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->StoreId;
    }

    /**
     * @param float $TotalAmount
     * @return $this
     */
    public function setTotalAmount($TotalAmount)
    {
        $this->TotalAmount = $TotalAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalAmount()
    {
        return $this->TotalAmount;
    }

    /**
     * @param float $TotalDiscount
     * @return $this
     */
    public function setTotalDiscount($TotalDiscount)
    {
        $this->TotalDiscount = $TotalDiscount;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalDiscount()
    {
        return $this->TotalDiscount;
    }

    /**
     * @param float $TotalNetAmount
     * @return $this
     */
    public function setTotalNetAmount($TotalNetAmount)
    {
        $this->TotalNetAmount = $TotalNetAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalNetAmount()
    {
        return $this->TotalNetAmount;
    }


}

