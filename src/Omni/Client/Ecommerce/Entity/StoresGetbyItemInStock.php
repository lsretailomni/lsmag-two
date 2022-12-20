<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class StoresGetbyItemInStock implements RequestInterface
{

    /**
     * @property string $itemId
     */
    protected $itemId = null;

    /**
     * @property string $variantId
     */
    protected $variantId = null;

    /**
     * @property double $latitude
     */
    protected $latitude = null;

    /**
     * @property double $longitude
     */
    protected $longitude = null;

    /**
     * @property double $maxDistance
     */
    protected $maxDistance = null;

    /**
     * @param string $itemId
     * @return $this
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @param string $variantId
     * @return $this
     */
    public function setVariantId($variantId)
    {
        $this->variantId = $variantId;
        return $this;
    }

    /**
     * @return string
     */
    public function getVariantId()
    {
        return $this->variantId;
    }

    /**
     * @param double $latitude
     * @return $this
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * @return double
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param double $longitude
     * @return $this
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return double
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param double $maxDistance
     * @return $this
     */
    public function setMaxDistance($maxDistance)
    {
        $this->maxDistance = $maxDistance;
        return $this;
    }

    /**
     * @return double
     */
    public function getMaxDistance()
    {
        return $this->maxDistance;
    }


}

