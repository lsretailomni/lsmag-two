<?php

namespace Ls\Customer\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
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
     * @return $this|void
     * @throws InvalidEnumException
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Customer $customer */
            $customer        = $observer->getEvent()->getCustomer();
            if (empty($customer->getData('ls_password'))) {
                return $this;
            }
            do {
                $userName = $this->contactHelper->generateRandomUsername();
            } while (($this->contactHelper->isUsernameExist($userName) ||
                ($this->lsr->isLSR(
                    $this->lsr->getCurrentStoreId(),
                    false,
                    $this->lsr->getCustomerIntegrationOnFrontend()
                ))) && $this->contactHelper->isUsernameExistInLsCentral($userName)
            );

            if ($customer->getId() && !empty($userName)
                && !empty($customer->getData('ls_password'))) {
                $customer->setData('lsr_username', $userName);
                if ($this->lsr->isLSR(
                    $this->lsr->getCurrentStoreId(),
                    false,
                    $this->lsr->getCustomerIntegrationOnFrontend()
                )) {
                    $contact = $this->contactHelper->getCustomerByUsernameOrEmailFromLsCentral(
                        $customer->getEmail(),
                        Entity\Enum\ContactSearchType::EMAIL
                    );
                    /** @var Entity\MemberContact $contact */
                    if (empty($contact)) {
                        $contact = $this->contactHelper->contact($customer);
                    }
                    if (is_object($contact) && $contact->getId()) {
                        $customer = $this->contactHelper->setCustomerAttributesValues($contact, $customer);

                        $userName = $customer->getData('lsr_username');
                        $result   = $this->contactHelper->forgotPassword($userName);

                        if ($result) {
                            $customer->setData('lsr_resetcode', $result);
                        }

                        $customer->setData('ls_password', null);
                        $this->customerResourceModel->save($customer);
                    }
                } else {
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
