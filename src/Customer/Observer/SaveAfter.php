<?php

namespace Ls\Customer\Observer;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;

/**
 * Observer responsible for observing customer_save_after
 */
class SaveAfter extends AbstractOmniObserver
{
    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws InvalidEnumException
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException|GuzzleException
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
                    $contact = $this->contactHelper->searchWithUsernameOrEmail($customer->getEmail());
                    /** @var Entity\MemberContact $contact */
                    if (empty($contact)) {
                        $contact = $this->contactHelper->contact($customer);
                    }
                    if (is_object($contact) && $contact->getCardId()) {
                        $customer = $this->contactHelper->setCustomerAttributesValues($contact, $customer);
                        $userName = $customer->getData('lsr_username');
                        $result = $this->contactHelper->forgotPassword($userName);

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
