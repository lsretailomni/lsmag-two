<?php
declare(strict_types=1);

namespace Ls\Webhooks\Block\Adminhtml\System\Config;

use \Ls\Webhooks\Model\Config\Source\ExpectedOrderStatus;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Email\Model\Template\Config;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Config\Model\Config\Source\Email\Template as DefaultEmailTemplates;

class OrderStatus extends AbstractFieldArray
{
    /**
     * @param Context $context
     * @param ElementFactory $elementFactory
     * @param Config $emailConfig
     * @param ExpectedOrderStatus $expectedOrderStatus
     * @param DefaultEmailTemplates $defaultTemplates
     * @param CollectionFactory $customTemplateFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        public ElementFactory $elementFactory,
        public Config $emailConfig,
        public ExpectedOrderStatus $expectedOrderStatus,
        public DefaultEmailTemplates $defaultTemplates,
        public CollectionFactory $customTemplateFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->addColumn('order_status', [
            'label' => __('Order Status'),
        ]);
        $this->addColumn('email_template', [
            'label' => __('Email Template'),
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
        if ($columnName === 'order_status' && isset($this->_columns[$columnName])) {
            return $this->renderSelectBoxNotificationName($columnName);
        }

        if ($columnName === 'email_template' && isset($this->_columns[$columnName])) {
            return $this->renderSelectBoxNotificationEmailTemplate($columnName);
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

    /**
     * Render Select box element for notification email template
     *
     * @param string $columnName
     *
     * @return string
     */
    private function renderSelectBoxNotificationEmailTemplate(string $columnName): string
    {
        return $this->elementFactory
            ->create('select')
            ->setForm($this->getData('form'))
            ->setData('name', $this->_getCellInputElementName($columnName))
            ->setData('html_id', $this->_getCellInputElementId('<%- _id %>', $columnName))
            ->setData('values', $this->toOptionArray())
            ->getElementHtml();
    }

    /**
     * Merged system emails and marketing emails
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        $default = $this->emailConfig->getAvailableTemplates();
        if (!empty($default)) {
            $options[] = ['label' => __('System Templates'), 'value' => $default];
        }

        $customOptions    = [];
        $customCollection = $this->customTemplateFactory->create();
        $customCollection->load();

        foreach ($customCollection as $template) {
            $customOptions[] = [
                'value' => $template->getId(),
                'label' => $template->getTemplateCode(),
            ];
        }

        if (!empty($customOptions)) {
            $options[] = ['label' => __('Custom Templates'), 'value' => $customOptions];
        }

        return $options;
    }
}
