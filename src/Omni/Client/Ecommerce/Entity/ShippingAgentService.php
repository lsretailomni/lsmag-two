<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

class ShippingAgentService extends Entity
{

    /**
     * @property string $Description
     */
    protected $Description = null;

    /**
     * @property string $ShippingTime
     */
    protected $ShippingTime = null;

    /**
     * @param string $Description
     * @return $this
     */
    public function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->Description;
    }

    /**
     * @param string $ShippingTime
     * @return $this
     */
    public function setShippingTime($ShippingTime)
    {
        $this->ShippingTime = $ShippingTime;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingTime()
    {
        return $this->ShippingTime;
    }


}

