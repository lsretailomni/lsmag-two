<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DeleteDatabtn extends Field
{
    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/Deletebtn.phtml');
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get Element Html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $scopeId      = $this->_request->getParam('store');
        $originalData = $element->getOriginalData();
        $buttonLabel  = $originalData['button_label'];
        $buttonUrl    = $originalData['button_url'];
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'button_url'   => $this->getUrl($buttonUrl, ['store' => $scopeId]),
                'html_id'      => $element->getHtmlId(),
            ]
        );

        return $this->_toHtml();
    }
}
