<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ItemCategory extends Entity
{

    /**
     * @property ArrayOfImageView $Images
     */
    protected $Images = null;

    /**
     * @property ArrayOfProductGroup $ProductGroups
     */
    protected $ProductGroups = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

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
     * @param ArrayOfProductGroup $ProductGroups
     * @return $this
     */
    public function setProductGroups($ProductGroups)
    {
        $this->ProductGroups = $ProductGroups;
        return $this;
    }

    /**
     * @return ArrayOfProductGroup
     */
    public function getProductGroups()
    {
        return $this->ProductGroups;
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


}

