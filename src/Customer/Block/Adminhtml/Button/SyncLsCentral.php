<?php

namespace Ls\Customer\Block\Adminhtml\Button;

use Magento\Backend\Block\Widget\Context;
use Magento\Customer\Block\Adminhtml\Edit\GenericButton;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SyncLsCentral
 * @package Ls\Customer\Block\Adminhtml\Button
 */
class SyncLsCentral extends GenericButton implements ButtonProviderInterface
{

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CustomerRegistry $customerRegistry
    ) {
        parent::__construct($context, $registry);
        $this->customerRegistry = $customerRegistry;
    }

    /**
     * Retrieve button-specified settings
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getButtonData()
    {
        $customerId = $this->getCustomerId();
        $data       = [];
        if ($customerId) {
            $customer = $this->customerRegistry->retrieve($customerId);
            if (empty($customer->getData('lsr_id')) || empty($customer->getData('lsr_username'))) {
                $message = __('Send customer to LS Central?');
                $data    = [
                    'label'      => __('Save to LS Central'),
                    'class'      => 'sync-ls-central',
                    'on_click'   => "confirmSetLocation('" . $message . "', '" . $this->getSyncUrl() . "')",
                    'sort_order' => 80,
                ];
            }
        }
        return $data;
    }

    /**
     * Get sync url
     *
     * @return string
     */
    public function getSyncUrl()
    {
        return $this->getUrl('lscustomer/account/sync', ['customer_id' => $this->getCustomerId()]);
    }
}
