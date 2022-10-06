<?php
declare(strict_types=1);

namespace Ls\Webhooks\Block\Adminhtml\System\Config;

use \Ls\Webhooks\Model\Config\Source\ExpectedOrderStatus;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;

class OrderStatusSms extends AbstractFieldArray
{
    /**
     * @var ElementFactory
     */
    private $elementFactory;

    /**
     * @var ExpectedOrderStatus
     */
    private $expectedOrderStatus;

    /**
     * @param Context $context
     * @param ElementFactory $elementFactory
     * @param ExpectedOrderStatus $expectedOrderStatus
     * @param array $data
     */
    public function __construct(
        Context $context,
        ElementFactory $elementFactory,
        ExpectedOrderStatus $expectedOrderStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->elementFactory      = $elementFactory;
        $this->expectedOrderStatus = $expectedOrderStatus;
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->addColumn('order_status_sms', [
            'label' => __('Order Status'),
        ]);
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add new order status notification');

        parent::_construct();
    }

    /**
     * @inheritDoc
     */
    public function renderCellTemplate($columnName): string
    {
        if ($columnName === 'order_status_sms' && isset($this->_columns[$columnName])) {
            return $this->renderSelectBoxNotificationName($columnName);
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Render Select box element for notification name
     *
     * @param string $columnName
     *
     * @return string
     */
    private function renderSelectBoxNotificationName(string $columnName): string
    {
        return $this->elementFactory
            ->create('select')
            ->setForm($this->getData('form'))
            ->setData('name', $this->_getCellInputElementName($columnName))
            ->setData('html_id', $this->_getCellInputElementId('<%- _id %>', $columnName))
            ->setData('values', $this->expectedOrderStatus->toOptionArray())
            ->getElementHtml();
    }
}
