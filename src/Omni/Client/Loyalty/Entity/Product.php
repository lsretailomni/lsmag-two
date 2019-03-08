<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 */


namespace Ls\Omni\Client\Loyalty\Entity;

class Product
{

    /**
     * @property int $DMT
     */
    protected $DMT = null;

    /**
     * @property string $Des
     */
    protected $Des = null;

    /**
     * @property string $Det
     */
    protected $Det = null;

    /**
     * @property string $Id
     */
    protected $Id = null;

    /**
     * @property ArrayOfImageView $Img
     */
    protected $Img = null;

    /**
     * @property ArrayOfstring $PMGIds
     */
    protected $PMGIds = null;

    /**
     * @property float $Pri
     */
    protected $Pri = null;

    /**
     * @property ArrayOfUnitOfMeasure $UOMs
     */
    protected $UOMs = null;

    /**
     * @property string $Uom
     */
    protected $Uom = null;

    /**
     * @param int $DMT
     * @return $this
     */
    public function setDMT($DMT)
    {
        $this->DMT = $DMT;
        return $this;
    }

    /**
     * @return int
     */
    public function getDMT()
    {
        return $this->DMT;
    }

    /**
     * @param string $Des
     * @return $this
     */
    public function setDes($Des)
    {
        $this->Des = $Des;
        return $this;
    }

    /**
     * @return string
     */
    public function getDes()
    {
        return $this->Des;
    }

    /**
     * @param string $Det
     * @return $this
     */
    public function setDet($Det)
    {
        $this->Det = $Det;
        return $this;
    }

    /**
     * @return string
     */
    public function getDet()
    {
        return $this->Det;
    }

    /**
     * @param string $Id
     * @return $this
     */
    public function setId($Id)
    {
        $this->Id = $Id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->Id;
    }

    /**
     * @param ArrayOfImageView $Img
     * @return $this
     */
    public function setImg($Img)
    {
        $this->Img = $Img;
        return $this;
    }

    /**
     * @return ArrayOfImageView
     */
    public function getImg()
    {
        return $this->Img;
    }

    /**
     * @param ArrayOfstring $PMGIds
     * @return $this
     */
    public function setPMGIds($PMGIds)
    {
        $this->PMGIds = $PMGIds;
        return $this;
    }

    /**
     * @return ArrayOfstring
     */
    public function getPMGIds()
    {
        return $this->PMGIds;
    }

    /**
     * @param float $Pri
     * @return $this
     */
    public function setPri($Pri)
    {
        $this->Pri = $Pri;
        return $this;
    }

    /**
     * @return float
     */
    public function getPri()
    {
        return $this->Pri;
    }

    /**
     * @param ArrayOfUnitOfMeasure $UOMs
     * @return $this
     */
    public function setUOMs($UOMs)
    {
        $this->UOMs = $UOMs;
        return $this;
    }

    /**
     * @return ArrayOfUnitOfMeasure
     */
    public function getUOMs()
    {
        return $this->UOMs;
    }

    /**
     * @param string $Uom
     * @return $this
     */
    public function setUom($Uom)
    {
        $this->Uom = $Uom;
        return $this;
    }

    /**
     * @return string
     */
    public function getUom()
    {
        return $this->Uom;
    }


}
