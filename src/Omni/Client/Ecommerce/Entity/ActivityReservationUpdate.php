<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityReservationUpdate implements RequestInterface
{
    /**
     * @property Reservation $request
     */
    protected $request = null;

    /**
     * @param Reservation $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Reservation
     */
    public function getRequest()
    {
        return $this->request;
    }
}

