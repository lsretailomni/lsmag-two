<?php
namespace Ls\Webhooks\Api\Data;

/**
 * Interface OrderLineInterface
 *
 * Represents an individual order line inside an order message
 */
interface OrderLineInterface
{
    public const LINE_NO = 'LineNo';
    public const EXT_LINE_STATUS = 'ExtLineStatus';
    public const ITEM_ID = 'ItemId';
    public const VARIANT_ID = 'VariantId';
    public const UNIT_OF_MEASURE_ID = 'UnitOfMeasureId';
    public const QUANTITY = 'Quantity';
    public const AMOUNT = 'Amount';
    public const NEW_STATUS = 'NewStatus';

    /**
     * Retrieve the line number of the order item
     *
     * @return int|null The line number in the order
     */
    public function getLineNo();

    /**
     * Set the line number of the order item
     *
     * @param int|null $lineNo The line number in the order
     * @return $this
     */
    public function setLineNo($lineNo);

    /**
     * Retrieve the external line status
     *
     * @return string|null Status of the order line from external system
     */
    public function getExtLineStatus();

    /**
     * Set the external line status
     *
     * @param string|null $extLineStatus Status of the order line from external system
     * @return $this
     */
    public function setExtLineStatus($extLineStatus);

    /**
     * Retrieve the Item ID
     *
     * @return string|null The identifier of the item in the order line
     */
    public function getItemId();

    /**
     * Set the Item ID
     *
     * @param string|null $itemId The identifier of the item in the order line
     * @return $this
     */
    public function setItemId($itemId);

    /**
     * Retrieve the Variant ID
     *
     * @return string|null The variant identifier for the item, if applicable
     */
    public function getVariantId();

    /**
     * Set the Variant ID
     *
     * @param string|null $variantId The variant identifier for the item, if applicable
     * @return $this
     */
    public function setVariantId($variantId);

    /**
     * Retrieve the unit of measure ID
     *
     * @return string|null Unit of measure for the quantity (e.g., pcs, kg)
     */
    public function getUnitOfMeasureId();

    /**
     * Set the unit of measure ID
     *
     * @param string|null $unitOfMeasureId Unit of measure for the quantity (e.g., pcs, kg)
     * @return $this
     */
    public function setUnitOfMeasureId($unitOfMeasureId);

    /**
     * Retrieve the quantity of the order line
     *
     * @return float|null Quantity of items in this line
     */
    public function getQuantity();

    /**
     * Set the quantity of the order line
     *
     * @param float|null $quantity Quantity of items in this line
     * @return $this
     */
    public function setQuantity($quantity);

    /**
     * Retrieve the amount for the order line
     *
     * @return float|null Total amount for this order line
     */
    public function getAmount();

    /**
     * Set the amount for the order line
     *
     * @param float|null $amount Total amount for this order line
     * @return $this
     */
    public function setAmount($amount);

    /**
     * Retrieve the new status for the order line
     *
     * @return string|null The updated status of this order line
     */
    public function getNewStatus();

    /**
     * Set the new status for the order line
     *
     * @param string|null $newStatus The updated status of this order line
     * @return $this
     */
    public function setNewStatus($newStatus);
}
