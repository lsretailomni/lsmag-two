<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class Product extends MenuItem
{
    /**
     * @property ArrayOfProductModifierGroup $ProductModifierGroups
     */
    protected $ProductModifierGroups = null;

    /**
     * @property ArrayOfUnitOfMeasure $UnitOfMeasures
     */
    protected $UnitOfMeasures = null;

    /**
     * @property string $DefaultUnitOfMeasure
     */
    protected $DefaultUnitOfMeasure = null;

    /**
     * @param ArrayOfProductModifierGroup $ProductModifierGroups
     * @return $this
     */
    public function setProductModifierGroups($ProductModifierGroups)
    {
        $this->ProductModifierGroups = $ProductModifierGroups;
        return $this;
    }

    /**
     * @return ArrayOfProductModifierGroup
     */
    public function getProductModifierGroups()
    {
        return $this->ProductModifierGroups;
    }

    /**
     * @param ArrayOfUnitOfMeasure $UnitOfMeasures
     * @return $this
     */
    public function setUnitOfMeasures($UnitOfMeasures)
    {
        $this->UnitOfMeasures = $UnitOfMeasures;
        return $this;
    }

    /**
     * @return ArrayOfUnitOfMeasure
     */
    public function getUnitOfMeasures()
    {
        return $this->UnitOfMeasures;
    }

    /**
     * @param string $DefaultUnitOfMeasure
     * @return $this
     */
    public function setDefaultUnitOfMeasure($DefaultUnitOfMeasure)
    {
        $this->DefaultUnitOfMeasure = $DefaultUnitOfMeasure;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultUnitOfMeasure()
    {
        return $this->DefaultUnitOfMeasure;
    }
}

