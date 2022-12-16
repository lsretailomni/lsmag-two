<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\Ecommerce\Entity\Enum\LinkStatus;
use Ls\Omni\Exception\InvalidEnumException;
use Ls\Omni\Client\RequestInterface;

class OneListLinking implements RequestInterface
{

    /**
     * @property string $oneListId
     */
    protected $oneListId = null;

    /**
     * @property string $cardId
     */
    protected $cardId = null;

    /**
     * @property string $email
     */
    protected $email = null;

    /**
     * @property string $phone
     */
    protected $phone = null;

    /**
     * @property LinkStatus $status
     */
    protected $status = null;

    /**
     * @param string $oneListId
     * @return $this
     */
    public function setOneListId($oneListId)
    {
        $this->oneListId = $oneListId;
        return $this;
    }

    /**
     * @return string
     */
    public function getOneListId()
    {
        return $this->oneListId;
    }

    /**
     * @param string $cardId
     * @return $this
     */
    public function setCardId($cardId)
    {
        $this->cardId = $cardId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardId()
    {
        return $this->cardId;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param LinkStatus|string $status
     * @return $this
     * @throws InvalidEnumException
     */
    public function setStatus($status)
    {
        if ( ! $status instanceof LinkStatus ) {
            if ( LinkStatus::isValid( $status ) )
                $status = new LinkStatus( $status );
            elseif ( LinkStatus::isValidKey( $status ) )
                $status = new LinkStatus( constant( "LinkStatus::$status" ) );
            elseif ( ! $status instanceof LinkStatus )
                throw new InvalidEnumException();
        }
        $this->status = $status->getValue();

        return $this;
    }

    /**
     * @return LinkStatus
     */
    public function getStatus()
    {
        return $this->status;
    }


}

