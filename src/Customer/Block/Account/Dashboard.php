<?php

namespace Ls\Customer\Block\Account;

use \Ls\Omni\Client\Ecommerce\Entity\Account;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;

/**
 * Class Dashboard
 * @package Ls\Customer\Block\Account
 */
class Dashboard extends Template
{

    /** @var LoyaltyHelper */
    private $loyaltyHelper;

    /**
     * Dashboard constructor.
     * @param Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        LoyaltyHelper $loyaltyHelper,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loyaltyHelper = $loyaltyHelper;
        $this->logger        = $logger;
    }

    /**
     * @return bool|Account
     */

    public function getMembersInfo()
    {
        $account = false;
        $result  = $this->loyaltyHelper->getMemberInfo();
        if ($result) {
            $account = $result->getAccount();
        }
        return $account;
    }
}
