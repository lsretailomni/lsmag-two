<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class MobileMenu extends Entity
{
    /**
     * @property ArrayOfDealModifierGroup $DealModifierGroups
     */
    protected $DealModifierGroups = null;

    /**
     * @property ArrayOfMenuDeal $Deals
     */
    protected $Deals = null;

    /**
     * @property ArrayOfIngredientItem $Items
     */
    protected $Items = null;

    /**
     * @property ArrayOfMenu $MenuNodes
     */
    protected $MenuNodes = null;

    /**
     * @property ArrayOfProductModifierGroup $ProductModifierGroups
     */
    protected $ProductModifierGroups = null;

    /**
     * @property ArrayOfProduct $Products
     */
    protected $Products = null;

    /**
     * @property ArrayOfRecipe $Recipes
     */
    protected $Recipes = null;

    /**
     * @property Currency $Currency
     */
    protected $Currency = null;

    /**
     * @property string $Version
     */
    protected $Version = null;

    /**
     * @param ArrayOfDealModifierGroup $DealModifierGroups
     * @return $this
     */
    public function setDealModifierGroups($DealModifierGroups)
    {
        $this->DealModifierGroups = $DealModifierGroups;
        return $this;
    }

    /**
     * @return ArrayOfDealModifierGroup
     */
    public function getDealModifierGroups()
    {
        return $this->DealModifierGroups;
    }

    /**
     * @param ArrayOfMenuDeal $Deals
     * @return $this
     */
    public function setDeals($Deals)
    {
        $this->Deals = $Deals;
        return $this;
    }

    /**
     * @return ArrayOfMenuDeal
     */
    public function getDeals()
    {
        return $this->Deals;
    }

    /**
     * @param ArrayOfIngredientItem $Items
     * @return $this
     */
    public function setItems($Items)
    {
        $this->Items = $Items;
        return $this;
    }

    /**
     * @return ArrayOfIngredientItem
     */
    public function getItems()
    {
        return $this->Items;
    }

    /**
     * @param ArrayOfMenu $MenuNodes
     * @return $this
     */
    public function setMenuNodes($MenuNodes)
    {
        $this->MenuNodes = $MenuNodes;
        return $this;
    }

    /**
     * @return ArrayOfMenu
     */
    public function getMenuNodes()
    {
        return $this->MenuNodes;
    }

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
     * @param ArrayOfProduct $Products
     * @return $this
     */
    public function setProducts($Products)
    {
        $this->Products = $Products;
        return $this;
    }

    /**
     * @return ArrayOfProduct
     */
    public function getProducts()
    {
        return $this->Products;
    }

    /**
     * @param ArrayOfRecipe $Recipes
     * @return $this
     */
    public function setRecipes($Recipes)
    {
        $this->Recipes = $Recipes;
        return $this;
    }

    /**
     * @return ArrayOfRecipe
     */
    public function getRecipes()
    {
        return $this->Recipes;
    }

    /**
     * @param Currency $Currency
     * @return $this
     */
    public function setCurrency($Currency)
    {
        $this->Currency = $Currency;
        return $this;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->Currency;
    }

    /**
     * @param string $Version
     * @return $this
     */
    public function setVersion($Version)
    {
        $this->Version = $Version;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->Version;
    }
}

