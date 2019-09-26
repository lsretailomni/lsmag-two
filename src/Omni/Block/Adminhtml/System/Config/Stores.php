<?php

namespace Ls\Omni\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use Magento\Backend\Block\Template\Context;

/**
 * Class Stores
 * @package Ls\Omni\Block\Adminhtml\System\Config
 */
class Stores extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Ls\Core\Model\LSR
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
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
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
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'validate_base_url',
                'label' => __('Validate Base URL'),
            ]
        );
        return $button->toHtml();
    }
}
