<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class InventoryResponse
{
    /**
     * @property string $BaseUnitOfMeasure
     */
    protected $BaseUnitOfMeasure = null;

    /**
     * @property string $ItemId
     */
    protected $ItemId = null;

    /**
     * @property float $QtyInventory
     */
    protected $QtyInventory = null;

    /**
     * @property string $StoreId
     */
    protected $StoreId = null;

    /**
     * @property string $VariantId
     */
    protected $VariantId = null;

    /**
     * @param string $BaseUnitOfMeasure
     * @return $this
     */
    public function setBaseUnitOfMeasure($BaseUnitOfMeasure)
    {
        $this->BaseUnitOfMeasure = $BaseUnitOfMeasure;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUnitOfMeasure()
    {
        return $this->BaseUnitOfMeasure;
    }

    /**
     * @param string $ItemId
     * @return $this
     */
    public function setItemId($ItemId)
    {
        $this->ItemId = $ItemId;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->ItemId;
    }

    /**
     * @param float $QtyInventory
     * @return $this
     */
    public function setQtyInventory($QtyInventory)
    {
        $this->QtyInventory = $QtyInventory;
        return $this;
    }

    /**
     * @return float
     */
    public function getQtyInventory()
    {
        return $this->QtyInventory;
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

