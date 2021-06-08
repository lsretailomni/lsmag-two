<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class OrderHospStatus
{

    /**
     * @property boolean $Confirmed
     */
    protected $Confirmed = null;

    /**
     * @property string $KotNo
     */
    protected $KotNo = null;

    /**
     * @property float $ProductionTime
     */
    protected $ProductionTime = null;

    /**
     * @property string $ReceiptNo
     */
    protected $ReceiptNo = null;

    /**
     * @property string $Status
     */
    protected $Status = null;

    /**
     * @param boolean $Confirmed
     * @return $this
     */
    public function setConfirmed($Confirmed)
    {
        $this->Confirmed = $Confirmed;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getConfirmed()
    {
        return $this->Confirmed;
    }

    /**
     * @param string $KotNo
     * @return $this
     */
    public function setKotNo($KotNo)
    {
        $this->KotNo = $KotNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getKotNo()
    {
        return $this->KotNo;
    }

    /**
     * @param float $ProductionTime
     * @return $this
     */
    public function setProductionTime($ProductionTime)
    {
        $this->ProductionTime = $ProductionTime;
        return $this;
    }

    /**
     * @return float
     */
    public function getProductionTime()
    {
        return $this->ProductionTime;
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
     * @param string $Status
     * @return $this
     */
    public function setStatus($Status)
    {
        $this->Status = $Status;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->Status;
    }


}
