<?php

namespace Ls\Customer\Controller\Adminhtml\Account;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Backend\App\Action;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Sync
 * @package Ls\Omni\Controller\Adminhtml\Account
 */
class Sync extends Action
{
    /**
     * @var ContactHelper
     */
    public $contactHelper;

    /**
     * @var CustomerRegistry
     */
    public $customerRegistry;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * Sync constructor.
     * @param Action\Context $context
     * @param ContactHelper $contactHelper
     * @param CustomerRegistry $customerRegistry
     * @param LSR $LSR
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context $context,
        ContactHelper $contactHelper,
        CustomerRegistry $customerRegistry,
        LSR $LSR,
        LoggerInterface $logger
    ) {
        $this->contactHelper    = $contactHelper;
        $this->customerRegistry = $customerRegistry;
        $this->lsr              = $LSR;
        $this->logger           = $logger;
        $this->messageManager   = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $customerId     = $this->getRequest()->getParam('customer_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('customer/index/edit', ['id' => $customerId]);
        try {
            $customer = $this->customerRegistry->retrieve($customerId);
            if ($this->lsr->isLSR($customer->getData('website_id'))) {
                $contact = $this->contactHelper->syncCustomerAndAddress($customer);
                if ($contact) {
                    $this->messageManager->addSuccessMessage(
                        __('Customer request has been sent to LS Central successfully.')
                    );
                } else {
                    $this->messageManager->addErrorMessage(__('Something went wrong while creating a customer. Please refer log for details.'));
                }
            } else {
                $this->messageManager->addErrorMessage(__('Omni service is down.'));
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect;
    }
}
