<?php

namespace Ls\Customer\Block\Account;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Account;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Dashboard
 * @package Ls\Customer\Block\Account
 */
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
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) && $this->lsr->getStoreConfig(
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
}
