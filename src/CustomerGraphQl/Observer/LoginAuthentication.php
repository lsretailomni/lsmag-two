<?php

namespace Ls\CustomerGraphQl\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * We need to check if login details exists
 */
class LoginAuthentication implements ObserverInterface
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var LSR @var */
    private $lsr;

    /**
     * LoginAuthentication constructor.
     * @param ContactHelper $contactHelper
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        LSR $LSR
    ) {
        $this->contactHelper = $contactHelper;
        $this->lsr           = $LSR;
    }

    /**
     * @param Observer $observer
     * @return $this
     * @throws GraphQlAuthenticationException
     * @throws GraphQlNoSuchEntityException
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $login             = [];
        $login['password'] = $observer->getData('password');
        $login['username'] = $observer->getData('model')->getEmail();
        if (!empty($login['username']) && !empty($login['password'])) {
            $username = $login['username'];
            $is_email = $this->contactHelper->isValid($username);
            if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                if ($is_email) {
                    $search = $this->contactHelper->search($username);
                    $found  = $search !== null
                        && ($search instanceof MemberContact)
                        && !empty($search->getEmail());
                    if (!$found) {
                        throw new GraphQlNoSuchEntityException(
                            __('Sorry! No account found with the provided email address.')
                        );
                    }
                    $username = $search->getUserName();
                }
                /** @var  MemberContact $result */
                $result = $this->contactHelper->login($username, $login['password']);
                if ($result == false) {
                    throw new GraphQlAuthenticationException(
                        __('Invalid LS Central login or password.')
                    );
                }

            }

        }

        return $this;
    }
}
