<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\AccountStatus;
use Ls\Omni\Client\Ecommerce\Entity\Enum\AccountType;
use Ls\Omni\Exception\InvalidEnumException;

class Account extends Entity
{
    /**
     * @property boolean $Blocked
     */
    protected $Blocked = null;

    /**
     * @property string $BlockedBy
     */
    protected $BlockedBy = null;

    /**
     * @property string $BlockedDate
     */
    protected $BlockedDate = null;

    /**
     * @property string $BlockedReason
     */
    protected $BlockedReason = null;

    /**
     * @property string $CustomerId
     */
    protected $CustomerId = null;

    /**
     * @property int $PointBalance
     */
    protected $PointBalance = null;

    /**
     * @property Scheme $Scheme
     */
    protected $Scheme = null;

    /**
     * @property AccountStatus $Status
     */
    protected $Status = null;

    /**
     * @property AccountType $Type
     */
    protected $Type = null;

    /**
     * @param boolean $Blocked
     * @return $this
     */
    public function setBlocked($Blocked)
    {
        $this->Blocked = $Blocked;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getBlocked()
    {
        return $this->Blocked;
    }

    /**
     * @param string $BlockedBy
     * @return $this
     */
    public function setBlockedBy($BlockedBy)
    {
        $this->BlockedBy = $BlockedBy;
        return $this;
    }

    /**
     * @return string
     */
    public function getBlockedBy()
    {
        return $this->BlockedBy;
    }

    /**
     * @param string $BlockedDate
     * @return $this
     */
    public function setBlockedDate($BlockedDate)
    {
        $this->BlockedDate = $BlockedDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getBlockedDate()
    {
        return $this->BlockedDate;
    }

    /**
     * @param string $BlockedReason
     * @return $this
     */
    public function setBlockedReason($BlockedReason)
    {
        $this->BlockedReason = $BlockedReason;
        return $this;
    }

    /**
     * @return string
     */
    public function getBlockedReason()
    {
        return $this->BlockedReason;
    }

    /**
     * @param string $CustomerId
     * @return $this
     */
    public function setCustomerId($CustomerId)
    {
        $this->CustomerId = $CustomerId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerId()
    {
        return $this->CustomerId;
    }

    /**
     * @param int $PointBalance
     * @return $this
     */
    public function setPointBalance($PointBalance)
    {
        $this->PointBalance = $PointBalance;
        return $this;
    }

    /**
     * @return int
     */
    public function getPointBalance()
    {
        return $this->PointBalance;
    }

    /**
     * @param Scheme $Scheme
     * @return $this
     */
    public function setScheme($Scheme)
    {
        $this->Scheme = $Scheme;
        return $this;
    }

    /**
     * @return Scheme
     */
    public function getScheme()
    {
        return $this->Scheme;
    }

    /**
     * @param AccountStatus|string $Status
     * @return $this
     * @throws InvalidEnumException
     */
    public function setStatus($Status)
    {
        if ( ! $Status instanceof AccountStatus ) {
            if ( AccountStatus::isValid( $Status ) )
                $Status = new AccountStatus( $Status );
            elseif ( AccountStatus::isValidKey( $Status ) )
                $Status = new AccountStatus( constant( "AccountStatus::$Status" ) );
            elseif ( ! $Status instanceof AccountStatus )
                throw new InvalidEnumException();
        }
        $this->Status = $Status->getValue();

        return $this;
    }

    /**
     * @return AccountStatus
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * @param AccountType|string $Type
     * @return $this
     * @throws InvalidEnumException
     */
    public function setType($Type)
    {
        if ( ! $Type instanceof AccountType ) {
            if ( AccountType::isValid( $Type ) )
                $Type = new AccountType( $Type );
            elseif ( AccountType::isValidKey( $Type ) )
                $Type = new AccountType( constant( "AccountType::$Type" ) );
            elseif ( ! $Type instanceof AccountType )
                throw new InvalidEnumException();
        }
        $this->Type = $Type->getValue();

        return $this;
    }

    /**
     * @return AccountType
     */
    public function getType()
    {
        return $this->Type;
    }
}

