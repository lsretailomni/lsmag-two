<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ItemCategoriesGetByIdResponse implements ResponseInterface
{
    /**
     * @property ItemCategory $ItemCategoriesGetByIdResult
     */
    protected $ItemCategoriesGetByIdResult = null;

    /**
     * @param ItemCategory $ItemCategoriesGetByIdResult
     * @return $this
     */
    public function setItemCategoriesGetByIdResult($ItemCategoriesGetByIdResult)
    {
        $this->ItemCategoriesGetByIdResult = $ItemCategoriesGetByIdResult;
        return $this;
    }

    /**
     * @return ItemCategory
     */
    public function getItemCategoriesGetByIdResult()
    {
        return $this->ItemCategoriesGetByIdResult;
    }

    /**
     * @return ItemCategory
     */
    public function getResult()
    {
        return $this->ItemCategoriesGetByIdResult;
    }
}

