<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class SearchRs extends Entity
{
    /**
     * @property ArrayOfItemCategory $ItemCategories
     */
    protected $ItemCategories = null;

    /**
     * @property ArrayOfLoyItem $Items
     */
    protected $Items = null;

    /**
     * @property ArrayOfNotification $Notifications
     */
    protected $Notifications = null;

    /**
     * @property ArrayOfOneList $OneLists
     */
    protected $OneLists = null;

    /**
     * @property ArrayOfProductGroup $ProductGroups
     */
    protected $ProductGroups = null;

    /**
     * @property ArrayOfProfile $Profiles
     */
    protected $Profiles = null;

    /**
     * @property ArrayOfSalesEntry $SalesEntries
     */
    protected $SalesEntries = null;

    /**
     * @property ArrayOfStore $Stores
     */
    protected $Stores = null;

    /**
     * @param ArrayOfItemCategory $ItemCategories
     * @return $this
     */
    public function setItemCategories($ItemCategories)
    {
        $this->ItemCategories = $ItemCategories;
        return $this;
    }

    /**
     * @return ArrayOfItemCategory
     */
    public function getItemCategories()
    {
        return $this->ItemCategories;
    }

    /**
     * @param ArrayOfLoyItem $Items
     * @return $this
     */
    public function setItems($Items)
    {
        $this->Items = $Items;
        return $this;
    }

    /**
     * @return ArrayOfLoyItem
     */
    public function getItems()
    {
        return $this->Items;
    }

    /**
     * @param ArrayOfNotification $Notifications
     * @return $this
     */
    public function setNotifications($Notifications)
    {
        $this->Notifications = $Notifications;
        return $this;
    }

    /**
     * @return ArrayOfNotification
     */
    public function getNotifications()
    {
        return $this->Notifications;
    }

    /**
     * @param ArrayOfOneList $OneLists
     * @return $this
     */
    public function setOneLists($OneLists)
    {
        $this->OneLists = $OneLists;
        return $this;
    }

    /**
     * @return ArrayOfOneList
     */
    public function getOneLists()
    {
        return $this->OneLists;
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
     * @param ArrayOfProfile $Profiles
     * @return $this
     */
    public function setProfiles($Profiles)
    {
        $this->Profiles = $Profiles;
        return $this;
    }

    /**
     * @return ArrayOfProfile
     */
    public function getProfiles()
    {
        return $this->Profiles;
    }

    /**
     * @param ArrayOfSalesEntry $SalesEntries
     * @return $this
     */
    public function setSalesEntries($SalesEntries)
    {
        $this->SalesEntries = $SalesEntries;
        return $this;
    }

    /**
     * @return ArrayOfSalesEntry
     */
    public function getSalesEntries()
    {
        return $this->SalesEntries;
    }

    /**
     * @param ArrayOfStore $Stores
     * @return $this
     */
    public function setStores($Stores)
    {
        $this->Stores = $Stores;
        return $this;
    }

    /**
     * @return ArrayOfStore
     */
    public function getStores()
    {
        return $this->Stores;
    }
}

