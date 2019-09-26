<?php

namespace Ls\Omni\Plugin\App\Action;

class Context
{
    const CONTEXT_CUSTOMER_EMAIL = 'logged_in_customer_email';
    const CONTEXT_CUSTOMER_ID = 'logged_in_customer_id';
    /**
     * @var \Magento\Customer\Model\Session
     */
    public $customerSession;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    public $httpContext;

    /**
     * Context constructor.
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Framework\App\Http\Context $httpContext
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
    }

    /**
     * @param \Magento\Framework\App\ActionInterface $subject
     * @param callable $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDispatch(
        \Magento\Framework\App\ActionInterface $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $customerEmail = "";
        if ($this->customerSession->getCustomerData()) {
            $customerEmail = $this->customerSession->getCustomerData()->getEmail();
        }
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            $customerId = 0;
        }

        $this->httpContext->setValue(
            Context::CONTEXT_CUSTOMER_ID,
            $customerId,
            false
        );
        $this->httpContext->setValue(
            Context::CONTEXT_CUSTOMER_EMAIL,
            $customerEmail,
            false
        );
        return $proceed($request);
    }
}
