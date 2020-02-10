<?php

namespace Ls\Omni\Plugin\App\Action;

use Closure;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;

class Context
{
    const CONTEXT_CUSTOMER_EMAIL = 'logged_in_customer_email';
    const CONTEXT_CUSTOMER_ID = 'logged_in_customer_id';
    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    public $httpContext;

    /**
     * Context constructor.
     * @param Proxy $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        Proxy $customerSession,
        \Magento\Framework\App\Http\Context $httpContext
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext     = $httpContext;
    }

    /**
     * @param ActionInterface $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDispatch(
        ActionInterface $subject,
        Closure $proceed,
        RequestInterface $request
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
