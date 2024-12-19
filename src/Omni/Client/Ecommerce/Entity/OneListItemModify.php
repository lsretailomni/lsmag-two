<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\RequestInterface;

class OneListItemModify implements RequestInterface
{
    /**
     * @property string $oneListId
     */
    protected $oneListId = null;

    /**
     * @property OneListItem $item
     */
    protected $item = null;

    /**
     * @property string $cardId
     */
    protected $cardId = null;

    /**
     * @property boolean $remove
     */
    protected $remove = null;

    /**
     * @property boolean $calculate
     */
    protected $calculate = null;

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
     * @param OneListItem $item
     * @return $this
     */
    public function setItem($item)
    {
        $this->item = $item;
        return $this;
    }

    /**
     * @return OneListItem
     */
    public function getItem()
    {
        return $this->item;
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
     * @param boolean $remove
     * @return $this
     */
    public function setRemove($remove)
    {
        $this->remove = $remove;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getRemove()
    {
        return $this->remove;
    }

    /**
     * @param boolean $calculate
     * @return $this
     */
    public function setCalculate($calculate)
    {
        $this->calculate = $calculate;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCalculate()
    {
        return $this->calculate;
    }
}

