<?php

namespace Ls\Customer\Block\Adminhtml\Tab;

use Magento\Backend\Block\Template;
use Magento\Framework\Phrase;
use Magento\Backend\Block\Template\Context;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Customer\Model\CustomerRegistry;

class MembershipInfo extends Template implements TabInterface
{
    /**
     * @param Context $context
     * @param CustomerRegistry $customerRegistry
     * @param array $data
     */
    public function __construct(
        public Context $context,
        public CustomerRegistry $customerRegistry,
        public array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get tab label
     *
     * @return Phrase
     */
    public function getTabLabel()
    {
        return __('LS Central Membership');
    }

    /**
     * Get tab title
     *
     * @return Phrase
     */
    public function getTabTitle()
    {
        return __('LS Central Membership');
    }

    /**
     * Can show label
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check to see if hidden
     *
     * @return false
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get tab class
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Get tab url
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Check to see if ajax loaded
     *
     * @return false
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
            $data = $this->_backendSession->getCustomerData();

            if (!isset($data['account']['email']) || !isset($data['account']['website_id'])) {
                return [
                    'lsr_id' => '',
                    'lsr_cardid' => '',
                    'lsr_username' => '',
                    'lsr_account_id' => ''
                ];
            }

            $customerDetails = $this->customerRegistry->retrieveByEmail(
                $data['account']['email'],
                $data['account']['website_id']
            );
            return [
                'lsr_id' => $customerDetails->getData('lsr_id'),
                'lsr_cardid' => $customerDetails->getData('lsr_cardid'),
                'lsr_username' => $customerDetails->getData('lsr_username'),
                'lsr_account_id' => $customerDetails->getData('lsr_account_id')
            ];
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return [];
        }
    }
}
