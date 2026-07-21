<?php

namespace Ls\Customer\Block\Order\Custom\Hyva;

use \Ls\Customer\Block\Order\AbstractOrderBlock;
use Magento\Customer\Model\Context;

class View extends AbstractOrderBlock
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/custom/view-hyva.phtml';
    // @codingStandardsIgnoreEnd

    /**
     * Return back url for logged in and guest users
     *
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $this->getUrl('sales/order/history');
        }
        return $this->getUrl('*/*/form');
    }
}
