<?php

namespace Ls\Customer\Block;

use Magento\Customer\Block\Account\SortLinkInterface;
use Magento\Customer\Model\Context;
use Magento\Framework\Phrase;

/**
 * Class Link
 * @package Ls\Customer\Block
 */
class Link extends \Magento\Framework\View\Element\Html\Link implements SortLinkInterface
{

    /** @var string */
    public $template = 'Ls_Customer::link.phtml';

    /** @var \Magento\Framework\App\Http\Context */
    public $httpContext;

    /**
     * Link constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        if ($this->isLoggedIn()) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl('customer/loyalty');
    }

    /**
     * @return Phrase
     */
    public function getLabel()
    {
        return __('Loyalty');
    }

    /**
     * {@inheritdoc}
     * @since 100.2.0
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * Is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH);
    }
}
