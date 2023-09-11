<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class ContactGetByCardId implements RequestInterface
{
    /**
     * @property string $cardId
     */
    protected $cardId = null;

    /**
     * @property int $numberOfTransReturned
     */
    protected $numberOfTransReturned = null;

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
     * @param int $numberOfTransReturned
     * @return $this
     */
    public function setNumberOfTransReturned($numberOfTransReturned)
    {
        $this->numberOfTransReturned = $numberOfTransReturned;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfTransReturned()
    {
        return $this->numberOfTransReturned;
    }
}

