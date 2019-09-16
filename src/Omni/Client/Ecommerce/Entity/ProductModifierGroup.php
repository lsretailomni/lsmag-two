<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ProductModifierGroup extends ModifierGroup
{

    /**
     * @property ArrayOfProductModifier $ProductModifiers
     */
    protected $ProductModifiers = null;

    /**
     * @property ArrayOfTextModifier $TextModifiers
     */
    protected $TextModifiers = null;

    /**
     * @param ArrayOfProductModifier $ProductModifiers
     * @return $this
     */
    public function setProductModifiers($ProductModifiers)
    {
        $this->ProductModifiers = $ProductModifiers;
        return $this;
    }

    /**
     * @return ArrayOfProductModifier
     */
    public function getProductModifiers()
    {
        return $this->ProductModifiers;
    }

    /**
     * @param ArrayOfTextModifier $TextModifiers
     * @return $this
     */
    public function setTextModifiers($TextModifiers)
    {
        $this->TextModifiers = $TextModifiers;
        return $this;
    }

    /**
     * @return ArrayOfTextModifier
     */
    public function getTextModifiers()
    {
        return $this->TextModifiers;
    }


}

