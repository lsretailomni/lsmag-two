<?php

namespace Ls\Customer\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;

/**
 * Class RegisterObserver
 * Customer Registration Observer
 */
class SaveAfter implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CustomerResourceModel $customerResourceModel */
    private $customerResourceModel;

    /** @var LSR @var */
    private $lsr;

    /**
     * SaveAfter constructor.
     * @param ContactHelper $contactHelper
     * @param LoggerInterface $logger
     * @param CustomerResourceModel $customerResourceModel
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        LoggerInterface $logger,
        CustomerResourceModel $customerResourceModel,
        LSR $LSR
    ) {
        $this->contactHelper         = $contactHelper;
        $this->logger                = $logger;
        $this->customerResourceModel = $customerResourceModel;
        $this->lsr                   = $LSR;
    }

    /**
     * After saving customer
     *
     * @param Observer $observer
     * @return SaveAfter
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Customer $customer */
            $customer = $observer->getEvent()->getCustomer();
            if (empty($customer->getData('ls_password'))) {
                return $this;
            }
            do {
                $userName = $this->contactHelper->generateRandomUsername();
            } while ($this->contactHelper->isUsernameExist($userName) ||
            $this->lsr->isLSR($this->lsr->getCurrentStoreId()) ?
                $this->contactHelper->isUsernameExistInLsCentral($userName) : false
            );

            if ($customer->getId() && !empty($userName)
                && !empty($customer->getData('ls_password'))) {
                $customer->setData('lsr_username', $userName);
                $customer->setData('password', $customer->decryptPassword($customer->getData('ls_password')));
                if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                    $contact    = $this->contactHelper->getCustomerByUsernameOrEmailFromLsCentral(
                        $customer->getEmail(),
                        Entity\Enum\ContactSearchType::EMAIL
                    );
                    /** @var Entity\MemberContact $contact */
                    if(empty($contact)) {
                        $contact = $this->contactHelper->contact($customer);
                    }
                    if (is_object($contact) && $contact->getId()) {
                        $customer = $this->contactHelper->setCustomerAttributesValues($contact, $customer);
                        $customer->setData('ls_password', null);
                        $this->customerResourceModel->save($customer);
                    }
                } else {
                    $customer->setData('lsr_password', $customer->getData('ls_password'));
                    $customer->setData('ls_password', null);
                    $this->customerResourceModel->save($customer);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}
