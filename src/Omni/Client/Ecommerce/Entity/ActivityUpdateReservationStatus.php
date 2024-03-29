<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityUpdateReservationStatus implements RequestInterface
{
    /**
     * @property string $reservationNo
     */
    protected $reservationNo = null;

    /**
     * @property string $setStatusCode
     */
    protected $setStatusCode = null;

    /**
     * @param string $reservationNo
     * @return $this
     */
    public function setReservationNo($reservationNo)
    {
        $this->reservationNo = $reservationNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getReservationNo()
    {
        return $this->reservationNo;
    }

    /**
     * @param string $setStatusCode
     * @return $this
     */
    public function setSetStatusCode($setStatusCode)
    {
        $this->setStatusCode = $setStatusCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getSetStatusCode()
    {
        return $this->setStatusCode;
    }
}

