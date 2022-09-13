<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ActivityMembershipSell implements RequestInterface
{
    /**
     * @property string $contactNo
     */
    protected $contactNo = null;

    /**
     * @property string $membersShipType
     */
    protected $membersShipType = null;

    /**
     * @param string $contactNo
     * @return $this
     */
    public function setContactNo($contactNo)
    {
        $this->contactNo = $contactNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getContactNo()
    {
        return $this->contactNo;
    }

    /**
     * @param string $membersShipType
     * @return $this
     */
    public function setMembersShipType($membersShipType)
    {
        $this->membersShipType = $membersShipType;
        return $this;
    }

    /**
     * @return string
     */
    public function getMembersShipType()
    {
        return $this->membersShipType;
    }
}

