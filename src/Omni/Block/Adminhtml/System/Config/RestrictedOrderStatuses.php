<?php

namespace Ls\Omni\Block\Adminhtml\System\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class RestrictedOrderStatuses implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $statusCollectionFactory;

    /**
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(
        CollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * Get all order statuses
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array_merge(
            [['value' => '', 'label' => __('-- Please Select --')]],
            $this->statusCollectionFactory->create()->toOptionArray()
        );
    }
}
