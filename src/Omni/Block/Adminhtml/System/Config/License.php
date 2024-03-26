<?php

namespace Ls\Omni\Block\Adminhtml\System\Config;

use Exception;
use \Ls\Core\Model\LSR;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;

class License extends Field
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @param Context $context
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        Context $context,
        LSR $lsr,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->lsr = $lsr;
    }

    /**
     * Render block HTML
     *
     * @param AbstractElement $element
     * @return string
     * @throws Exception
     */
    public function render(AbstractElement $element)
    {
        $websiteId = $element->getScopeId();

        $str = $this->lsr->getCentralVersion($websiteId, ScopeInterface::SCOPE_WEBSITES);
        if ($str) {
            $centralVersion = strstr($str, " ", true);

            if (version_compare($centralVersion, '24.0.0.0', '<') || $element->getValue() === null) {
                return '';
            }
        }

        return parent::render($element);
    }

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $validClass   = 'valid-license';
        $invalidClass = 'invalid-license';
        $html         = "<div class='control-value ";
        $html         .= $element->getValue() == "1" ? $validClass : $invalidClass;
        $html         .= "'>";
        $html         .= $element->getValue() == "1" ? __('Valid') : __('Invalid');
        $html         .= "</div>";

        return $html;
    }
}
