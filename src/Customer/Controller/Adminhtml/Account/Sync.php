<?php

namespace Ls\Customer\Controller\Adminhtml\Account;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Backend\App\Action;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Sync extends Action
{
    /**
     * Sync constructor.
     * @param Action\Context $context
     * @param ContactHelper $contactHelper
     * @param CustomerRegistry $customerRegistry
     * @param LSR $lsr
     * @param LoggerInterface $logger
     */
    public function __construct(
        public Action\Context $context,
        public ContactHelper $contactHelper,
        public CustomerRegistry $customerRegistry,
        public LSR $lsr,
        public LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Entry point for the controller
     *
     * Responsible to sync customer with central
     *
     * @return Redirect
     * @throws GuzzleException
     */
    public function execute()
    {
        $customerId     = $this->getRequest()->getParam('customer_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('customer/index/edit', ['id' => $customerId]);
        try {
            $customer = $this->customerRegistry->retrieve($customerId);
            $this->lsr->setStoreId($customer->getStoreId());

            if ($this->lsr->isLSR($customer->getData('website_id'), ScopeInterface::SCOPE_WEBSITE)) {
                $contact = $this->contactHelper->syncCustomerAndAddress($customer);

                if ($contact) {
                    $this->messageManager->addSuccessMessage(
                        __('Customer request has been sent to LS Central successfully.')
                    );
                } else {
                    $this->messageManager->addErrorMessage(
                        __('Something went wrong while creating a customer. Please refer log for details.')
                    );
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
