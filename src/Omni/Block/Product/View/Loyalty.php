<?php

namespace Ls\Omni\Block\Product\View;

class Loyalty extends \Magento\Framework\View\Element\Template
{

    /** @var \Magento\Customer\Model\Session */
    protected $customerSession;

    /**
     * Loyalty constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }


    /**
     * @return bool
     */
    public function isLoggedIn()
    {

        return $this->customerSession->isLoggedIn();
    }
}
