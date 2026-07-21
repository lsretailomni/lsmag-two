<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

/**
 * Dynamic rows backend block for Voucher / Gift Card configuration.
 *
 * Renders a multi-row table in the admin system configuration allowing
 * merchants to define multiple voucher or gift card types with the
 * following columns:
 *  - Code          : The voucher/gift card identifier code
 *  - Item Type     : Whether it is an Item or an Income/Account type
 *  - Item/Account No : The corresponding item or account number in LS Central
 *  - Tender Type   : The LS Central tender type used when posting payment lines
 */
class VoucherGiftCardConfig extends AbstractFieldArray
{
    private $itemTypeRenderer;
    private $tenderTypeRenderer;

    /**
     * Prepare columns for the dynamic rows table.
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('code', [
            'label' => __('Code'),
            'class' => 'required-entry',
            'style' => 'width: 180px'
        ]);
        $this->addColumn('item_type', [
            'label'    => __('Item Type'),
            'renderer' => $this->getItemTypeRenderer(),
            'style'    => 'width: 120px'
        ]);
        $this->addColumn('item_account_no', [
            'label' => __('Item/Account No'),
            'class' => 'required-entry',
            'style' => 'width: 110px'
        ]);
        $this->addColumn('tender_type', [
            'label'    => __('Tender Type'),
            'renderer' => $this->getTenderTypeRenderer(),
            'style'    => 'width: 260px'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Voucher/Gift Card');
    }

    /**
     * Pre-select saved option values when rendering existing rows.
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $itemType = $row->getData('item_type');
        if ($itemType !== null) {
            $options['option_' . $this->getItemTypeRenderer()->calcOptionHash($itemType)] = 'selected="selected"';
        }

        $tenderType = $row->getData('tender_type');
        if ($tenderType !== null) {
            $options['option_' . $this->getTenderTypeRenderer()->calcOptionHash($tenderType)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Retrieve (or lazily create) the Item Type dropdown column renderer.
     *
     * @return VoucherItemType
     */
    private function getItemTypeRenderer()
    {
        if (!$this->itemTypeRenderer) {
            $this->itemTypeRenderer = $this->getLayout()->createBlock(
                VoucherItemType::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->itemTypeRenderer;
    }

    /**
     * Retrieve (or lazily create) the Tender Type dropdown column renderer.
     * Tender types are populated from LS Central via ReplicationHelper.
     *
     * @return VoucherTenderTypeColumn
     */
    private function getTenderTypeRenderer()
    {
        if (!$this->tenderTypeRenderer) {
            $this->tenderTypeRenderer = $this->getLayout()->createBlock(
                VoucherTenderTypeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->tenderTypeRenderer;
    }
}
