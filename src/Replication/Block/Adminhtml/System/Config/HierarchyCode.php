<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchy;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Model\ReplHierarchyRepository;
use \Ls\Replication\Model\ReplHierarchySearchResults;
use Magento\Framework\Option\ArrayInterface;

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
     * @param LSR $lsr
     */
    public function __construct(
        ReplHierarchyRepository $replHierarchyRepository,
        ReplicationHelper $replicationHelper,
        LSR $lsr
    ) {
        $this->replHierarchyRepository = $replHierarchyRepository;
        $this->replicationHelper       = $replicationHelper;
        $this->lsr                     = $lsr;
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
        if ($this->lsr->isLSR()) {
            /**
             * We want to populate all the Hierarchy codes first even though if the replication is not done.
             */
            $criteria = $this->replicationHelper->buildCriteriaForNewItems();
            /** @var ReplHierarchySearchResults $replHierarchyRepository */
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
                        /** @var ReplHierarchy $item */
                        foreach ($data as $item) {
                            if ($item instanceof ReplHierarchy) {
                                $hierarchyCodes[] = [
                                    'value' => $item->getId(),
                                    'label' => __($item->getDescription())
                                ];
                            }
                        }
                    } elseif ($data instanceof ReplHierarchy) {
                        $item             = $data;
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
