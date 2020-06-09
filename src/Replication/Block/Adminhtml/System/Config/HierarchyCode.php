<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchy;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class HierarchyCode
 * @package Ls\Replication\Block\Adminhtml\System\Config
 */
class HierarchyCode implements OptionSourceInterface
{
    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /** @var RequestInterface */
    public $request;

    /**
     * HierarchyCode constructor.
     * @param ReplicationHelper $replicationHelper
     * @param LSR $lsr
     * @param RequestInterface $request
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        LSR $lsr,
        RequestInterface $request
    ) {
        $this->replicationHelper = $replicationHelper;
        $this->lsr               = $lsr;
        $this->request           = $request;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $hierarchyCodes[] = [
            'value' => '',
            'label' => __('Please select your hierarchy code')
        ];
        // Get current Website Id.
        $websiteId = (int)$this->request->getParam('website');
        if ($this->lsr->isLSR($websiteId, 'website')) {
            $hierarchyData = $this->replicationHelper->getHierarchyByStore($websiteId);
            if ($hierarchyData) {
                $data = $hierarchyData->getHierarchies()->getReplHierarchy();
                foreach ($data as $item) {
                    if ($item instanceof ReplHierarchy) {
                        $hierarchyCodes[] = [
                            'value' => $item->getId(),
                            'label' => __($item->getDescription())
                        ];
                    }
                }
            }
        } else {
            $this->replicationHelper->getLogger()->debug('Store not set');
        }
        return $hierarchyCodes;
    }
}
