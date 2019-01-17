<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

class DeleteDatabtn extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/Deletebtn.phtml');
        }
        return $this;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = $originalData['button_label'];
        $buttonUrl = $originalData['button_url'];
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'button_url' => $this->getBaseUrl() . $buttonUrl,
                'html_id' => $element->getHtmlId(),
            ]
        );

        return $this->_toHtml();
    }
}
