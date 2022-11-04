<?php

namespace Ls\Replication\Api\Data;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Framework\Api\ExtensibleDataInterface;

interface ImageIdProductAttributeMediaGalleryInterface extends ProductAttributeMediaGalleryEntryInterface
{
    const IMAGE_ID = 'image_id';

    /**
     * Get Image Id
     * @return string
     */
    public function getImageId();

    /**
     * Set Image Id
     * @param $imageId
     * @return $this
     */
    public function setImageId($imageId);
}
