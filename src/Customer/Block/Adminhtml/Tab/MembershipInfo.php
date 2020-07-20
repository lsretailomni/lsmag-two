<?php

namespace Ls\Customer\Block\Adminhtml\Tab;

use Magento\Backend\Block\Template;
use Magento\Framework\Phrase;
use Magento\Backend\Block\Template\Context;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Customer\Model\CustomerRegistry;

/**
 * Customer membership view block
 * Class MembershipInfo
 * @package Ls\Customer\Block\Adminhtml\Tab
 */
class MembershipInfo extends Template implements TabInterface
{

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * MembershipInfo constructor.
     * @param Context $context
     * @param CustomerRegistry $customerRegistry
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerRegistry $customerRegistry,
        array $data = []
    ) {
        $this->customerRegistry = $customerRegistry;
        parent::__construct($context, $data);
    }

    /**
     * @return Phrase
     */
    public function getTabLabel()
    {
        return __('LS Central Membership');
    }

    /**
     * @return Phrase
     */
    public function getTabTitle()
    {
        return __('LS Central Membership');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * Returns customer's membership details
     *
     * @return array
     */
    public function getMembershipInfo()
    {
        try {
            $data            = $this->_backendSession->getCustomerData();
            $customerDetails = $this->customerRegistry->retrieveByEmail($data['account']['email'], $data['account']['website_id']);
            return [
                'lsr_id'       => $customerDetails->getData('lsr_id'),
                'lsr_cardid'   => $customerDetails->getData('lsr_cardid'),
                'lsr_username' => $customerDetails->getData('lsr_username')
            ];
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return [];
        }
    }
}
