<?php
/**
 * THIS IS AN AUTOGENERATED FILE
 * DO NOT MODIFY
 * @codingStandardsIgnoreFile
 */


namespace Ls\Omni\Client\Ecommerce\Entity;

use Ls\Omni\Client\ResponseInterface;

class GiftCardGetHistoryResponse implements ResponseInterface
{
    /**
     * @property ArrayOfGiftCardEntry $GiftCardGetHistoryResult
     */
    protected $GiftCardGetHistoryResult = null;

    /**
     * @param ArrayOfGiftCardEntry $GiftCardGetHistoryResult
     * @return $this
     */
    public function setGiftCardGetHistoryResult($GiftCardGetHistoryResult)
    {
        $this->GiftCardGetHistoryResult = $GiftCardGetHistoryResult;
        return $this;
    }

    /**
     * @return ArrayOfGiftCardEntry
     */
    public function getGiftCardGetHistoryResult()
    {
        return $this->GiftCardGetHistoryResult;
    }

    /**
     * @return ArrayOfGiftCardEntry
     */
    public function getResult()
    {
        return $this->GiftCardGetHistoryResult;
    }
}

