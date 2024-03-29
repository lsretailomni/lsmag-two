<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\OfferDiscountType;
use Ls\Omni\Exception\InvalidEnumException;

class OneListPublishedOffer extends Entity
{
    /**
     * @property string $CreateDate
     */
    protected $CreateDate = null;

    /**
     * @property int $LineNumber
     */
    protected $LineNumber = null;

    /**
     * @property OfferDiscountType $Type
     */
    protected $Type = null;

    /**
     * @param string $CreateDate
     * @return $this
     */
    public function setCreateDate($CreateDate)
    {
        $this->CreateDate = $CreateDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreateDate()
    {
        return $this->CreateDate;
    }

    /**
     * @param int $LineNumber
     * @return $this
     */
    public function setLineNumber($LineNumber)
    {
        $this->LineNumber = $LineNumber;
        return $this;
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->LineNumber;
    }

    /**
     * @param OfferDiscountType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof OfferDiscountType ) {
            if ( OfferDiscountType::isValid( $Type ) )
                $Type = new OfferDiscountType( $Type );
            elseif ( OfferDiscountType::isValidKey( $Type ) )
                $Type = new OfferDiscountType( constant( "OfferDiscountType::$Type" ) );
            elseif ( ! $Type instanceof OfferDiscountType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();

        return $this;
    }

    /**
     * @return OfferDiscountType
     */
    public function getType()
    {
        return $this->Type;
    }
}

