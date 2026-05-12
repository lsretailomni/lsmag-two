<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Data;

use Ls\Webhooks\Api\Data\OrderLineInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Class OrderLine
 *
 * Implementation of OrderLineInterface
 */
class OrderLine extends AbstractExtensibleModel implements OrderLineInterface
{
    /**
     * @inheritdoc
     */
    public function getLineNo()
    {
        return $this->getData(self::LINE_NO);
    }

    /**
     * @inheritdoc
     */
    public function setLineNo($lineNo)
    {
        return $this->setData(self::LINE_NO, $lineNo);
    }

    /**
     * @inheritdoc
     */
    public function getExtLineStatus()
    {
        return $this->getData(self::EXT_LINE_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setExtLineStatus($extLineStatus)
    {
        return $this->setData(self::EXT_LINE_STATUS, $extLineStatus);
    }

    /**
     * @inheritdoc
     */
    public function getItemId()
    {
        return $this->getData(self::ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setItemId($itemId)
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    /**
     * @inheritdoc
     */
    public function getVariantId()
    {
        return $this->getData(self::VARIANT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setVariantId($variantId)
    {
        return $this->setData(self::VARIANT_ID, $variantId);
    }

    /**
     * @inheritdoc
     */
    public function getUnitOfMeasureId()
    {
        return $this->getData(self::UNIT_OF_MEASURE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setUnitOfMeasureId($unitOfMeasureId)
    {
        return $this->setData(self::UNIT_OF_MEASURE_ID, $unitOfMeasureId);
    }

    /**
     * @inheritdoc
     */
    public function getQuantity()
    {
        return $this->getData(self::QUANTITY);
    }

    /**
     * @inheritdoc
     */
    public function setQuantity($quantity)
    {
        return $this->setData(self::QUANTITY, $quantity);
    }

    /**
     * @inheritdoc
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * @inheritdoc
     */
    public function getNewStatus()
    {
        return $this->getData(self::NEW_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setNewStatus($newStatus)
    {
        return $this->setData(self::NEW_STATUS, $newStatus);
    }
}
