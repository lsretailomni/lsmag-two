<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Framework\Option\ArrayInterface;
use \Ls\Replication\Model\ReplHierarchyRepository;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Core\Model\LSR;

/**
 * Class HierarchyCode
 * @package Ls\Replication\Block\Adminhtml\System\Config
 */
class HierarchyCode implements ArrayInterface
{
    /** @var ReplHierarchyRepository */
    public $replHierarchyRepository;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /**
     * HierarchyCode constructor.
     * @param ReplHierarchyRepository $replHierarchyRepository
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        ReplHierarchyRepository $replHierarchyRepository,
        ReplicationHelper $replicationHelper,
        LSR $lsr
    ) {
        $this->replHierarchyRepository = $replHierarchyRepository;
        $this->replicationHelper = $replicationHelper;
        $this->lsr = $lsr;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $hierarchyCodes = [];
        if ($this->lsr->isLSR()) {
            /**
             * We want to populate all the Hierarchy codes first even though if the replication is not done.
             */
            $criteria = $this->replicationHelper->buildCriteriaForNewItems();
            /** @var \Ls\Replication\Model\ReplHierarchySearchResults $replHierarchyRepository */
            $replHierarchyRepository = $this->replHierarchyRepository->getList($criteria);

            if ($replHierarchyRepository->getTotalCount() > 0) {
                // We got the data from our system, so use that.
                /** @var \Ls\Replication\Model\ReplHierarchy $hierarchy */
                foreach ($replHierarchyRepository->getItems() as $hierarchy) {
                    $hierarchyCodes[] = [
                        'value' => $hierarchy->getData('nav_id'),
                        'label' => __($hierarchy->getData('Description'))
                    ];
                }
            } else {
                $hierarchyData = $this->replicationHelper->getHierarchyByStore();
                if ($hierarchyData) {
                    $data = $hierarchyData->getHierarchies()->getReplHierarchy();
                    if (is_array($data)) {
                        /** @var \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchy $item */
                        foreach ($data as $item) {
                            if ($item instanceof \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchy) {
                                $hierarchyCodes[] = [
                                    'value' => $item->getId(),
                                    'label' => __($item->getDescription())
                                ];
                            }
                        }
                    } elseif ($data instanceof \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchy) {
                        $item = $data;
                        $hierarchyCodes[] = [
                            'value' => $item->getId(),
                            'label' => __($item->getDescription())
                        ];
                    }
                }
            }
        } else {
            $this->replicationHelper->getLogger()->debug('Store not set');
            $hierarchyCodes[] = [
                'value' => '',
                'label' => __('Please select the Store First')
            ];
        }
        return $hierarchyCodes;
    }
}
