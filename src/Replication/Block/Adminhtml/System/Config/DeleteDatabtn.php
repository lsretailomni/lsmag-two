<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class DeleteDatabtn extends Field
{
    /**
     * @var Share
     */
    public $shareConfig;

    /**
     * @param Context $context
     * @param Share $shareConfig
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        Share $shareConfig,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
        $this->shareConfig = $shareConfig;
    }

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
        $scopeId = $this->_request->getParam('store');

        if ($element->getId() == 'ls_mag_restore_database_customers' &&
            $scopeId != '' &&
            $this->shareConfig->isGlobalScope()
        ) {
            return '';
        }

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
