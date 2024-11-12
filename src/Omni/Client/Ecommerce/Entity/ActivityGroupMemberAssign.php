<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityGroupMemberAssign implements RequestInterface
{
    /**
     * @property string $reservationNo
     */
    protected $reservationNo = null;

    /**
     * @property int $memberSequence
     */
    protected $memberSequence = null;

    /**
     * @property int $lineNo
     */
    protected $lineNo = null;

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
     * @param int $memberSequence
     * @return $this
     */
    public function setMemberSequence($memberSequence)
    {
        $this->memberSequence = $memberSequence;
        return $this;
    }

    /**
     * @return int
     */
    public function getMemberSequence()
    {
        return $this->memberSequence;
    }

    /**
     * @param int $lineNo
     * @return $this
     */
    public function setLineNo($lineNo)
    {
        $this->lineNo = $lineNo;
        return $this;
    }

    /**
     * @return int
     */
    public function getLineNo()
    {
        return $this->lineNo;
    }
}
