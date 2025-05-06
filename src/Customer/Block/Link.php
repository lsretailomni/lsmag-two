<?php

namespace Ls\Customer\Block;

use Magento\Customer\Block\Account\SortLinkInterface;
use Magento\Customer\Model\Context;
use Magento\Framework\Phrase;
use \Ls\Core\Model\LSR;

class Link extends \Magento\Framework\View\Element\Html\Link implements SortLinkInterface
{
    /** @var string */
    public $template = 'Ls_Customer::link.phtml';

    /** @var \Magento\Framework\App\Http\Context */
    public $httpContext;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        LSR $lsr,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->lsr = $lsr;
        parent::__construct($context, $data);
    }

    /**
     * Check if we need to render block or not
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function _toHtml()
    {
        if ($this->isLoggedIn() &&
            $this->lsr->isLSR(
                $this->lsr->getCurrentStoreId(),
                false,
                $this->lsr->getCustomerIntegrationOnFrontend()
            )) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Get anchor tag href attribute
     *
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl('customer/loyalty');
    }

    /**
     * Get anchor tag label attribute
     *
     * @return Phrase
     */
    public function getLabel()
    {
        return __('Loyalty');
    }

    /**
     * Get link sort order
     *
     * @return array|int|mixed|null
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
