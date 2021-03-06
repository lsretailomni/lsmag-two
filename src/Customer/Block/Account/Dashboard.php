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
     * @return bool|Account
     */

    public function getMembersInfo()
    {
        $account = false;
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $result = $this->loyaltyHelper->getMemberInfo();
            if ($result) {
                $account = $result->getAccount();
            }
        }
        return $account;
    }
}
