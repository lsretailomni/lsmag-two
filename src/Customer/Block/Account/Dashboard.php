<?php

namespace Ls\Customer\Block\Account;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Account;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Dashboard extends Template
{

    /** @var LoyaltyHelper */
    public $loyaltyHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * Dashboard constructor.
     * @param Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        Context $context,
        LoyaltyHelper $loyaltyHelper,
        LSR $lsr,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loyaltyHelper = $loyaltyHelper;
        $this->lsr           = $lsr;
    }

    /**
     * Get members info
     *
     * @return false|Account|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMembersInfo()
    {
        $account = false;
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getCustomerIntegrationOnFrontend()
        ) &&
            $this->lsr->getStoreConfig(
                LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_CONTACT_BY_CARD_ID_API_CALL,
                $this->lsr->getCurrentStoreId()
            )) {
            $result = $this->loyaltyHelper->getMemberInfo();
            if ($result) {
                $account = $result->getAccount();
            }
        }
        return $account;
    }

    public function getPointBalanceExpiry($account)
    {
        $totalPoints = 0;
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) && $this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_POINTS_EXPIRY_CHECK,
            $this->lsr->getCurrentStoreId()
        )) {
            $totalPoints = $this->loyaltyHelper->getPointBalanceExpirySum();
        }
        if ($totalPoints) {
            $expiryInterval = $this->lsr->getStoreConfig(
                LSR::SC_LOYALTY_POINTS_EXPIRY_NOTIFICATION_INTERVAL,
                $this->lsr->getCurrentStoreId()
            );
            return $totalPoints." to be expired in ".$expiryInterval." days.";
        }
        return $totalPoints;
    }
}
