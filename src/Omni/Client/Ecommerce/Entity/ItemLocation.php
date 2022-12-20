<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ItemLocation
{

    /**
     * @property string $SectionCode
     */
    protected $SectionCode = null;

    /**
     * @property string $SectionDescription
     */
    protected $SectionDescription = null;

    /**
     * @property string $ShelfCode
     */
    protected $ShelfCode = null;

    /**
     * @property string $ShelfDescription
     */
    protected $ShelfDescription = null;

    /**
     * @property string $StoreId
     */
    protected $StoreId = null;

    /**
     * @param string $SectionCode
     * @return $this
     */
    public function setSectionCode($SectionCode)
    {
        $this->SectionCode = $SectionCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getSectionCode()
    {
        return $this->SectionCode;
    }

    /**
     * @param string $SectionDescription
     * @return $this
     */
    public function setSectionDescription($SectionDescription)
    {
        $this->SectionDescription = $SectionDescription;
        return $this;
    }

    /**
     * @return string
     */
    public function getSectionDescription()
    {
        return $this->SectionDescription;
    }

    /**
     * @param string $ShelfCode
     * @return $this
     */
    public function setShelfCode($ShelfCode)
    {
        $this->ShelfCode = $ShelfCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getShelfCode()
    {
        return $this->ShelfCode;
    }

    /**
     * @param string $ShelfDescription
     * @return $this
     */
    public function setShelfDescription($ShelfDescription)
    {
        $this->ShelfDescription = $ShelfDescription;
        return $this;
    }

    /**
     * @return string
     */
    public function getShelfDescription()
    {
        return $this->ShelfDescription;
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


}

