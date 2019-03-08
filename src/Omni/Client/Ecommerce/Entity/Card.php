<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\CardStatus;
use Ls\Omni\Exception\InvalidEnumException;

class Card extends Entity
{

    /**
     * @property string $BlockedBy
     */
    protected $BlockedBy = null;

    /**
     * @property string $BlockedReason
     */
    protected $BlockedReason = null;

    /**
     * @property string $ClubId
     */
    protected $ClubId = null;

    /**
     * @property string $ContactId
     */
    protected $ContactId = null;

    /**
     * @property string $DateBlocked
     */
    protected $DateBlocked = null;

    /**
     * @property boolean $LinkedToAccount
     */
    protected $LinkedToAccount = null;

    /**
     * @property CardStatus $Status
     */
    protected $Status = null;

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
     * @param string $ClubId
     * @return $this
     */
    public function setClubId($ClubId)
    {
        $this->ClubId = $ClubId;
        return $this;
    }

    /**
     * @return string
     */
    public function getClubId()
    {
        return $this->ClubId;
    }

    /**
     * @param string $ContactId
     * @return $this
     */
    public function setContactId($ContactId)
    {
        $this->ContactId = $ContactId;
        return $this;
    }

    /**
     * @return string
     */
    public function getContactId()
    {
        return $this->ContactId;
    }

    /**
     * @param string $DateBlocked
     * @return $this
     */
    public function setDateBlocked($DateBlocked)
    {
        $this->DateBlocked = $DateBlocked;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateBlocked()
    {
        return $this->DateBlocked;
    }

    /**
     * @param boolean $LinkedToAccount
     * @return $this
     */
    public function setLinkedToAccount($LinkedToAccount)
    {
        $this->LinkedToAccount = $LinkedToAccount;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getLinkedToAccount()
    {
        return $this->LinkedToAccount;
    }

    /**
     * @param CardStatus|string $Status
     * @return $this
     * @throws InvalidEnumException
     */
    public function setStatus($Status)
    {
        if ( ! $Status instanceof CardStatus ) {
            if ( CardStatus::isValid( $Status ) ) 
                $Status = new CardStatus( $Status );
            elseif ( CardStatus::isValidKey( $Status ) ) 
                $Status = new CardStatus( constant( "CardStatus::$Status" ) );
            elseif ( ! $Status instanceof CardStatus )
                throw new InvalidEnumException();
        }
        $this->Status = $Status->getValue();
        
        return $this;
    }

    /**
     * @return CardStatus
     */
    public function getStatus()
    {
        return $this->Status;
    }


}
