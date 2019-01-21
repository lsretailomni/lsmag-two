<?php

namespace Ls\Customer\Block\Account;

use Ls\Omni\Helper\LoyaltyHelper;

/**
 * Class Dashboard
 * @package Ls\Customer\Block\Account
 */
class Dashboard extends \Magento\Framework\View\Element\Template
{

    /** @var LoyaltyHelper */
    private $loyaltyHelper;

    /**
     * Dashboard constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        LoyaltyHelper $loyaltyHelper,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loyaltyHelper = $loyaltyHelper;
        $this->logger = $logger;
    }

    /**
     * @return bool|\Ls\Omni\Client\Ecommerce\Entity\Account
     */

    public function getMembersInfo()
    {
        $account = false;
        $result = $this->loyaltyHelper->getMemberInfo();
        if ($result) {
            $account = $result->getAccount();
        }
        return $account;
    }
}
