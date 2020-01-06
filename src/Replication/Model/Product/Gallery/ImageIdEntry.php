<?php

namespace Ls\Replication\Model\Product\Gallery;

use \Ls\Replication\Api\Data\ImageIdProductAttributeMediaGalleryInterface;
use Magento\Catalog\Model\Product\Gallery\Entry;

class ImageIdEntry extends Entry implements ImageIdProductAttributeMediaGalleryInterface
{
    /**
     * @return mixed|string
     */
    public function getImageId()
    {
        return $this->getData(self::IMAGE_ID);
    }

    /**
     * @param $imageId
     * @return ImageIdProductAttributeMediaGalleryInterface|ImageIdEntry
     */
    public function setImageId($imageId)
    {
        return $this->setData(self::IMAGE_ID, $imageId);
    }

}