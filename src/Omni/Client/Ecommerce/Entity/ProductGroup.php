<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ProductGroup extends Entity
{
    /**
     * @property ArrayOfImageView $Images
     */
    protected $Images = null;

    /**
     * @property ArrayOfLoyItem $Items
     */
    protected $Items = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $ItemCategoryId
     */
    protected $ItemCategoryId = null;

    /**
     * @param ArrayOfImageView $Images
     * @return $this
     */
    public function setImages($Images)
    {
        $this->Images = $Images;
        return $this;
    }

    /**
     * @return ArrayOfImageView
     */
    public function getImages()
    {
        return $this->Images;
    }

    /**
     * @param ArrayOfLoyItem $Items
     * @return $this
     */
    public function setItems($Items)
    {
        $this->Items = $Items;
        return $this;
    }

    /**
     * @return ArrayOfLoyItem
     */
    public function getItems()
    {
        return $this->Items;
    }

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * @param string $ItemCategoryId
     * @return $this
     */
    public function setItemCategoryId($ItemCategoryId)
    {
        $this->ItemCategoryId = $ItemCategoryId;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemCategoryId()
    {
        return $this->ItemCategoryId;
    }
}

