<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class VSDateSchedule
{

    /**
     * @property ArrayOfVSDateScheduleLine $Lines
     */
    protected $Lines = null;

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property boolean $Fridays
     */
    protected $Fridays = null;

    /**
     * @property string $Id
     */
    protected $Id = null;

    /**
     * @property boolean $Mondays
     */
    protected $Mondays = null;

    /**
     * @property boolean $Saturdays
     */
    protected $Saturdays = null;

    /**
     * @property boolean $Sundays
     */
    protected $Sundays = null;

    /**
     * @property boolean $Thursdays
     */
    protected $Thursdays = null;

    /**
     * @property boolean $Tuesdays
     */
    protected $Tuesdays = null;

    /**
     * @property boolean $ValidAllWeekdays
     */
    protected $ValidAllWeekdays = null;

    /**
     * @property boolean $Wednesdays
     */
    protected $Wednesdays = null;

    /**
     * @param ArrayOfVSDateScheduleLine $Lines
     * @return $this
     */
    public function setLines($Lines)
    {
        $this->Lines = $Lines;
        return $this;
    }

    /**
     * @return ArrayOfVSDateScheduleLine
     */
    public function getLines()
    {
        return $this->Lines;
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
     * @param boolean $Fridays
     * @return $this
     */
    public function setFridays($Fridays)
    {
        $this->Fridays = $Fridays;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getFridays()
    {
        return $this->Fridays;
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
     * @param boolean $Mondays
     * @return $this
     */
    public function setMondays($Mondays)
    {
        $this->Mondays = $Mondays;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getMondays()
    {
        return $this->Mondays;
    }

    /**
     * @param boolean $Saturdays
     * @return $this
     */
    public function setSaturdays($Saturdays)
    {
        $this->Saturdays = $Saturdays;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSaturdays()
    {
        return $this->Saturdays;
    }

    /**
     * @param boolean $Sundays
     * @return $this
     */
    public function setSundays($Sundays)
    {
        $this->Sundays = $Sundays;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSundays()
    {
        return $this->Sundays;
    }

    /**
     * @param boolean $Thursdays
     * @return $this
     */
    public function setThursdays($Thursdays)
    {
        $this->Thursdays = $Thursdays;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getThursdays()
    {
        return $this->Thursdays;
    }

    /**
     * @param boolean $Tuesdays
     * @return $this
     */
    public function setTuesdays($Tuesdays)
    {
        $this->Tuesdays = $Tuesdays;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getTuesdays()
    {
        return $this->Tuesdays;
    }

    /**
     * @param boolean $ValidAllWeekdays
     * @return $this
     */
    public function setValidAllWeekdays($ValidAllWeekdays)
    {
        $this->ValidAllWeekdays = $ValidAllWeekdays;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getValidAllWeekdays()
    {
        return $this->ValidAllWeekdays;
    }

    /**
     * @param boolean $Wednesdays
     * @return $this
     */
    public function setWednesdays($Wednesdays)
    {
        $this->Wednesdays = $Wednesdays;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getWednesdays()
    {
        return $this->Wednesdays;
    }


}

