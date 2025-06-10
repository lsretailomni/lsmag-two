<?php

namespace Ls\Customer\Observer;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use Magento\Framework\Event\Observer;

/**
 * Observer responsible for customer registration
 */
class RegisterObserver extends AbstractOmniObserver
{
    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws GuzzleException
     */
    public function execute(Observer $observer)
    {
        try {
            $session = $this->customerSession;
            $customer = $session->getCustomer();
            $additionalParams = $this->contactHelper->getValue();

            if (empty($customer->getId())) {
                $customer = $this->contactHelper->getCustomerByEmail($additionalParams['email']);
            }
            if ($customer->getId() && !empty($additionalParams['lsr_username'])
                && !empty($additionalParams['password'])
            ) {
                $customer->setData('lsr_username', $additionalParams['lsr_username']);
                $customer->setData('password', $additionalParams['password']);
                if ($this->lsr->isLSR(
                    $this->lsr->getCurrentStoreId(),
                    false,
                    $this->lsr->getCustomerIntegrationOnFrontend()
                )) {
                    /** @var Entity\MemberContact $contact */
                    if (is_array($additionalParams) && $additionalParams['lsr_id']) {
                        $customer = $this->contactHelper->setCustomerAttributesValues($additionalParams, $customer);
                        $this->customerResourceModel->save($customer);
                        $contact = $additionalParams['contact'];
                        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $contact);
                        $this->contactHelper->setLsrAccountIdInCustomerSession($customer->getData('lsr_account_id'));
                        $this->contactHelper->setLsrIdInCustomerSession($customer->getData('lsr_id'));
                        $this->contactHelper->setCardIdInCustomerSession($customer->getData('lsr_cardid'));
                    }
                    $loginResult = $this->contactHelper->login(
                        $customer->getData('lsr_username'),
                        $additionalParams['password']
                    );
                    if (!$loginResult) {
                        $this->logger->error('Invalid Omni login or Omni password');
                        return $this;
                    } else {
                        $loginResult = $this->contactHelper->flattenModel($loginResult);
                        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
                        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $loginResult);
//                        $this->contactHelper->updateBasketAndWishlistAfterLogin($loginResult);
                    }
                } else {
                    $customer->setData(
                        'lsr_password',
                        $this->contactHelper->encryptPassword($additionalParams['password'])
                    );
                    $this->customerResourceModel->save($customer);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}
