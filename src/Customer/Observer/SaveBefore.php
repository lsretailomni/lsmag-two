<?php

namespace Ls\Customer\Observer;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Observer responsible for observing customer_save_before
 */
class SaveBefore extends AbstractOmniObserver
{
    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws AlreadyExistsException~
     * @throws InputException
     * @throws InvalidEnumException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function execute(Observer $observer)
    {
        $parameters = $observer->getEvent()->getDataObject();
        $parameters->setData('ls_validation', true);
        if (empty($parameters->getData('password_hash')) && empty($parameters->getData('ls_password'))
            && empty($parameters->getData('lsr_cardid'))) {
            $masterPassword = $this->lsr->getStoreConfig(LSR::SC_MASTER_PASSWORD, $this->lsr->getCurrentStoreId());
            $parameters->setData('ls_password', $this->contactHelper->encryptPassword($masterPassword));
            $parameters->setData('ls_validation', false);
        }
        if (empty($parameters->getData('ls_password'))) {
            return $this;
        }

        if (!empty($parameters->getData('email')) && $parameters->getData('ls_validation')) {
            if ($this->lsr->isLSR(
                $this->lsr->getCurrentStoreId(),
                false,
                $this->lsr->getCustomerIntegrationOnFrontend()
            )) {
                if ($this->contactHelper->isEmailExistInLsCentral($parameters->getData('email'))) {
                    $parameters->setData('ls_validation', false);
                    throw new AlreadyExistsException(
                        __(
                            'There is already an account with this email address.
                             If you are sure that it is your email address,
                             please proceed to login or use different email address.'
                        )
                    );
                }
            }
        } else {
            if ($parameters->getData('ls_validation')) {
                throw new InputException(__('Your email address is invalid.'));
            }
        }
        return $this;
    }
}
