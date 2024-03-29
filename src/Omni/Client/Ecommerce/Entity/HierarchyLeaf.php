<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\HierarchyLeafType;
use Ls\Omni\Exception\InvalidEnumException;

class HierarchyLeaf extends HierarchyPoint
{
    /**
     * @property ArrayOfItemModifier $Modifiers
     */
    protected $Modifiers = null;

    /**
     * @property ArrayOfItemRecipe $Recipies
     */
    protected $Recipies = null;

    /**
     * @property float $AddedAmount
     */
    protected $AddedAmount = null;

    /**
     * @property string $DealLineCode
     */
    protected $DealLineCode = null;

    /**
     * @property int $DealLineNo
     */
    protected $DealLineNo = null;

    /**
     * @property string $ItemNo
     */
    protected $ItemNo = null;

    /**
     * @property string $ItemUOM
     */
    protected $ItemUOM = null;

    /**
     * @property int $LineNo
     */
    protected $LineNo = null;

    /**
     * @property int $MaxSelection
     */
    protected $MaxSelection = null;

    /**
     * @property int $MinSelection
     */
    protected $MinSelection = null;

    /**
     * @property float $Prepayment
     */
    protected $Prepayment = null;

    /**
     * @property int $SortOrder
     */
    protected $SortOrder = null;

    /**
     * @property HierarchyLeafType $Type
     */
    protected $Type = null;

    /**
     * @property string $VariantCode
     */
    protected $VariantCode = null;

    /**
     * @param ArrayOfItemModifier $Modifiers
     * @return $this
     */
    public function setModifiers($Modifiers)
    {
        $this->Modifiers = $Modifiers;
        return $this;
    }

    /**
     * @return ArrayOfItemModifier
     */
    public function getModifiers()
    {
        return $this->Modifiers;
    }

    /**
     * @param ArrayOfItemRecipe $Recipies
     * @return $this
     */
    public function setRecipies($Recipies)
    {
        $this->Recipies = $Recipies;
        return $this;
    }

    /**
     * @return ArrayOfItemRecipe
     */
    public function getRecipies()
    {
        return $this->Recipies;
    }

    /**
     * @param float $AddedAmount
     * @return $this
     */
    public function setAddedAmount($AddedAmount)
    {
        $this->AddedAmount = $AddedAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getAddedAmount()
    {
        return $this->AddedAmount;
    }

    /**
     * @param string $DealLineCode
     * @return $this
     */
    public function setDealLineCode($DealLineCode)
    {
        $this->DealLineCode = $DealLineCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getDealLineCode()
    {
        return $this->DealLineCode;
    }

    /**
     * @param int $DealLineNo
     * @return $this
     */
    public function setDealLineNo($DealLineNo)
    {
        $this->DealLineNo = $DealLineNo;
        return $this;
    }

    /**
     * @return int
     */
    public function getDealLineNo()
    {
        return $this->DealLineNo;
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
     * @param string $ItemUOM
     * @return $this
     */
    public function setItemUOM($ItemUOM)
    {
        $this->ItemUOM = $ItemUOM;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemUOM()
    {
        return $this->ItemUOM;
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
     * @param int $MaxSelection
     * @return $this
     */
    public function setMaxSelection($MaxSelection)
    {
        $this->MaxSelection = $MaxSelection;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxSelection()
    {
        return $this->MaxSelection;
    }

    /**
     * @param int $MinSelection
     * @return $this
     */
    public function setMinSelection($MinSelection)
    {
        $this->MinSelection = $MinSelection;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinSelection()
    {
        return $this->MinSelection;
    }

    /**
     * @param float $Prepayment
     * @return $this
     */
    public function setPrepayment($Prepayment)
    {
        $this->Prepayment = $Prepayment;
        return $this;
    }

    /**
     * @return float
     */
    public function getPrepayment()
    {
        return $this->Prepayment;
    }

    /**
     * @param int $SortOrder
     * @return $this
     */
    public function setSortOrder($SortOrder)
    {
        $this->SortOrder = $SortOrder;
        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->SortOrder;
    }

    /**
     * @param HierarchyLeafType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof HierarchyLeafType ) {
            if ( HierarchyLeafType::isValid( $Type ) )
                $Type = new HierarchyLeafType( $Type );
            elseif ( HierarchyLeafType::isValidKey( $Type ) )
                $Type = new HierarchyLeafType( constant( "HierarchyLeafType::$Type" ) );
            elseif ( ! $Type instanceof HierarchyLeafType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();

        return $this;
    }

    /**
     * @return HierarchyLeafType
     */
    public function getType()
    {
        return $this->Type;
    }

    /**
     * @param string $VariantCode
     * @return $this
     */
    public function setVariantCode($VariantCode)
    {
        $this->VariantCode = $VariantCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getVariantCode()
    {
        return $this->VariantCode;
    }
}

