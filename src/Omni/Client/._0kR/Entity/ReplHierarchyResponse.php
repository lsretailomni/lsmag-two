<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ReplHierarchyResponse
{
    /**
     * @property ArrayOfReplHierarchy $Hierarchies
     */
    protected $Hierarchies = null;

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
     * @param ArrayOfReplHierarchy $Hierarchies
     * @return $this
     */
    public function setHierarchies($Hierarchies)
    {
        $this->Hierarchies = $Hierarchies;
        return $this;
    }

    /**
     * @return ArrayOfReplHierarchy
     */
    public function getHierarchies()
    {
        return $this->Hierarchies;
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
