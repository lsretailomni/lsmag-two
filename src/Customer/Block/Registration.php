<?php

namespace Ls\Customer\Block;

use \Ls\Core\Model\LSR;
use Magento\Checkout\Block\Registration as CheckoutRegistration;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Registration as CustomerRegistration;
use Magento\Customer\Model\Session\Proxy as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Address\Validator;

/**
 * Class Registration
 * @package Ls\Customer\Block
 */
class Registration extends CheckoutRegistration
{

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * Registration constructor.
     * @param Template\Context $context
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param CustomerRegistration $registration
     * @param AccountManagementInterface $accountManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param Validator $addressValidator
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        CustomerRegistration $registration,
        AccountManagementInterface $accountManagement,
        OrderRepositoryInterface $orderRepository,
        Validator $addressValidator,
        LSR $lsr,
        array $data = []
    ) {
        $this->lsr = $lsr;
        parent::__construct(
            $context,
            $checkoutSession,
            $customerSession,
            $registration,
            $accountManagement,
            $orderRepository,
            $addressValidator,
            $data
        );
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if ($this->lsr->isLSR()) {
            return '';
        } else {
            return parent::toHtml();
        }
    }
}
