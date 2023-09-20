<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ReplItemUnitOfMeasureResponse
{
    /**
     * @property ArrayOfReplItemUnitOfMeasure $ItemUnitOfMeasures
     */
    protected $ItemUnitOfMeasures = null;

    /**
     * @property string $LastKey
     */
    protected $LastKey = null;

    /**
     * @property string $MaxKey
     */
    protected $MaxKey = null;

    /**
     * @property int $RecordsRemaining
     */
    protected $RecordsRemaining = null;

    /**
     * @param ArrayOfReplItemUnitOfMeasure $ItemUnitOfMeasures
     * @return $this
     */
    public function setItemUnitOfMeasures($ItemUnitOfMeasures)
    {
        $this->ItemUnitOfMeasures = $ItemUnitOfMeasures;
        return $this;
    }

    /**
     * @return ArrayOfReplItemUnitOfMeasure
     */
    public function getItemUnitOfMeasures()
    {
        return $this->ItemUnitOfMeasures;
    }

    /**
     * @param string $LastKey
     * @return $this
     */
    public function setLastKey($LastKey)
    {
        $this->LastKey = $LastKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastKey()
    {
        return $this->LastKey;
    }

    /**
     * @param string $MaxKey
     * @return $this
     */
    public function setMaxKey($MaxKey)
    {
        $this->MaxKey = $MaxKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getMaxKey()
    {
        return $this->MaxKey;
    }

    /**
     * @param int $RecordsRemaining
     * @return $this
     */
    public function setRecordsRemaining($RecordsRemaining)
    {
        $this->RecordsRemaining = $RecordsRemaining;
        return $this;
    }

    /**
     * @return int
     */
    public function getRecordsRemaining()
    {
        return $this->RecordsRemaining;
    }
}
