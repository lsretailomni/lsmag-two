<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use \Ls\Replication\Block\Adminhtml\System\Config\TenderPaymentMapping\TenderTypesColumn;
use \Ls\Replication\Block\Adminhtml\System\Config\TenderPaymentMapping\PaymentMethodsColumn;
use Magento\Framework\View\Element\BlockInterface;

/**
 * Class for tender type and payment mapping
 */
class TenderPaymentMapping extends AbstractFieldArray
{
    /**
     * @var PaymentMethodsColumn
     */
    private $paymentMethods;

    /**
     * @var TenderTypesColumn
     */
    private $tenderTypes;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('payment_method', [
            'label'    => __('Payment Methods'),
            'renderer' => $this->getPaymentMethodsRenderer()
        ]);
        $this->addColumn('tender_type', [
            'label'    => __('Tender Types'),
            'renderer' => $this->getTenderTypesRenderer()
        ]);
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $paymentMethods = $row->getPaymentMethods();
        if ($paymentMethods !== null) {
            $options['option_' . $this->getPaymentMethodsRenderer()->calcOptionHash($paymentMethods)] =
                'selected="selected"';
        }

        $tenderTypes = $row->getTenderTypes();
        if ($tenderTypes !== null) {
            $options['option_' . $this->getTenderTypesRenderer()->calcOptionHash($tenderTypes)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Getting Payment Types Renderer
     *
     * @return PaymentMethodsColumn|BlockInterface
     * @throws LocalizedException
     */
    private function getPaymentMethodsRenderer()
    {
        if (!$this->paymentMethods) {
            $this->paymentMethods = $this->getLayout()->createBlock(
                PaymentMethodsColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->paymentMethods;
    }

    /**
     * Getting Tender Types Renderer
     *
     * @return TenderTypesColumn
     * @throws LocalizedException
     */
    private function getTenderTypesRenderer()
    {
        if (!$this->tenderTypes) {
            $this->tenderTypes = $this->getLayout()->createBlock(
                TenderTypesColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->tenderTypes;
    }
}
