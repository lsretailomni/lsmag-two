<?php

namespace Ls\Omni\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class Stores extends Field
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreLine
    protected $_template = 'Ls_Omni::system/config/store.phtml';

    /**
     * Stores constructor.
     * @param Context $context
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        protected Context $context,
        public LSR $lsr,
        protected array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Unset some non-related element parameters
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Get load store url
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('omni/system_config/loadStore');
    }

    /**
     * Get load hierarchy url
     *
     * @return string
     */
    public function getAjaxHierarchyUrl()
    {
        return $this->getUrl('omni/system_config/loadHierarchy');
    }

    /**
     * Get load ajax url
     *
     * @return string
     */
    public function getAjaxStoreTenderTypesUrl()
    {
        return $this->getUrl('omni/system_config/loadTenderType');
    }

    /**
     * Get website Id
     *
     * @return mixed
     */
    public function getWebsiteId()
    {
        return $this->_request->getParam('website');
    }

    /**
     * Get button html
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            Button::class
        )->setData(
            [
                'id'    => 'validate_base_url',
                'label' => __('Validate Base URL'),
            ]
        );
        return $button->toHtml();
    }
}
