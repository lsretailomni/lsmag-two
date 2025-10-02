<?php

namespace Ls\Customer\Block\Account;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetMemberContactInfo_GetMemberContactInfo;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Dashboard extends Template
{
    /**
     * @param Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        Context $context,
        public LoyaltyHelper $loyaltyHelper,
        public LSR $lsr,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get members info
     *
     * @return bool|GetMemberContactInfo_GetMemberContactInfo
     * @throws GuzzleException|NoSuchEntityException
     */
    public function getMembersInfo()
    {
        $result = false;
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
        }
        return $result;
    }

    /**
     * Get loyalty points balance
     *
     * @return float
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function getLoyaltyPointsBalance()
    {
        return $this->loyaltyHelper->getLoyaltyPointsAvailableToCustomer();
    }

    /**
     * Get point balance expiry
     *
     * @return false|float|int|string
     * @throws GuzzleException
     * @throws NoSuchEntityException
     */
    public function getPointBalanceExpiry()
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
            return $totalPoints . " to be expired in " . $expiryInterval . " days.";
        }
        return $totalPoints;
    }

    /**
     * Get next scheme
     *
     * @param string $currentClubCode
     * @param string $currentSequence
     * @return mixed|null
     * @throws NoSuchEntityException
     */
    public function getNextScheme(string $currentClubCode, string $currentSequence)
    {
        return $this->loyaltyHelper->getNextScheme($currentClubCode, $currentSequence);
    }
}
