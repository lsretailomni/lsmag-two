<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Framework\Option\ArrayInterface;
use Ls\Replication\Model\ReplHierarchyRepository;
use Ls\Replication\Helper\ReplicationHelper;

class HierarchyCode implements ArrayInterface
{
    /** @var ReplHierarchyRepository */
    protected $replHierarchyRepository;

    /** @var ReplicationHelper */
    protected $replicationHelper;

    /**
     * HierarchyCode constructor.
     * @param ReplHierarchyRepository $replHierarchyRepository
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        ReplHierarchyRepository $replHierarchyRepository,
        ReplicationHelper $replicationHelper
    )
    {
        $this->replHierarchyRepository = $replHierarchyRepository;
        $this->replicationHelper = $replicationHelper;
    }

    public function toOptionArray()
    {
        $criteria = $this->replicationHelper->buildCriteriaForNewItems();
        /** @var \Ls\Replication\Model\ReplHierarchySearchResults $replHierarchyRepository */
        $replHierarchyRepository = $this->replHierarchyRepository->getList($criteria);
        $hierarchyCodes = array();
        $countItems = count($replHierarchyRepository->getItems());
        /** @var \Ls\Replication\Model\ReplHierarchy $hierarchy */
        foreach ($replHierarchyRepository->getItems() as $hierarchy) {
            $hierarchyCodes[] = [
                'value' => $hierarchy->getData('nav_id'),
                'label' => __($hierarchy->getData('Description'))
            ];
        }
        return $hierarchyCodes;
    }
}
