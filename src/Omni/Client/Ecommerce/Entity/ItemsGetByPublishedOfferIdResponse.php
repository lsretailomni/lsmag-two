<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class ItemsGetByPublishedOfferIdResponse implements ResponseInterface
{
    /**
     * @property ArrayOfLoyItem $ItemsGetByPublishedOfferIdResult
     */
    protected $ItemsGetByPublishedOfferIdResult = null;

    /**
     * @param ArrayOfLoyItem $ItemsGetByPublishedOfferIdResult
     * @return $this
     */
    public function setItemsGetByPublishedOfferIdResult($ItemsGetByPublishedOfferIdResult)
    {
        $this->ItemsGetByPublishedOfferIdResult = $ItemsGetByPublishedOfferIdResult;
        return $this;
    }

    /**
     * @return ArrayOfLoyItem
     */
    public function getItemsGetByPublishedOfferIdResult()
    {
        return $this->ItemsGetByPublishedOfferIdResult;
    }

    /**
     * @return ArrayOfLoyItem
     */
    public function getResult()
    {
        return $this->ItemsGetByPublishedOfferIdResult;
    }
}

