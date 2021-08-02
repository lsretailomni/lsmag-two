<?php

namespace Ls\Omni\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Stores
 * @package Ls\Omni\Block\Adminhtml\System\Config
 */
class Stores extends Field
{
    /**
     * @var LSR
     */
    public $lsr;

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
        Context $context,
        LSR $lsr,
        array $data = []
    ) {
        $this->lsr = $lsr;
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
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('omni/system_config/loadStore');
    }

    /**
     * @return string
     */
    public function getAjaxHierarchyUrl()
    {
        return $this->getUrl('omni/system_config/loadHierarchy');
    }

    /**
     * @return string
     */
    public function getAjaxStoreTenderTypesUrl()
    {
        return $this->getUrl('omni/system_config/loadTenderType');
    }

    /**
     * @return mixed
     */
    public function getWebsiteId()
    {
        return $this->_request->getParam('website');
    }

    /**
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
