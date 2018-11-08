<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use Magento\Framework\Option\ArrayInterface;
use Ls\Replication\Model\ReplHierarchyRepository;
use Ls\Replication\Helper\ReplicationHelper;
use Ls\Core\Model\LSR;

class HierarchyCode implements ArrayInterface
{
    /** @var ReplHierarchyRepository */
    protected $replHierarchyRepository;

    /** @var ReplicationHelper */
    protected $replicationHelper;

    /** @var LSR  */
    protected $_lsr;

    /**
     * HierarchyCode constructor.
     * @param ReplHierarchyRepository $replHierarchyRepository
     * @param ReplicationHelper $replicationHelper
     */
    public function __construct(
        ReplHierarchyRepository $replHierarchyRepository,
        ReplicationHelper $replicationHelper,
        LSR $lsr
    )
    {
        $this->replHierarchyRepository = $replHierarchyRepository;
        $this->replicationHelper = $replicationHelper;
        $this->_lsr                =    $lsr;
    }

    public function toOptionArray()
    {

        $hierarchyCodes = array();
        if($this->_lsr->isLSR()) {
            /**
             * We want to populate all the Hierarchy codes first even though if the replicaiton is not done.
             */

            $criteria = $this->replicationHelper->buildCriteriaForNewItems();
            /** @var \Ls\Replication\Model\ReplHierarchySearchResults $replHierarchyRepository */
            $replHierarchyRepository = $this->replHierarchyRepository->getList($criteria);

            $countItems = count($replHierarchyRepository->getItems());
            /** @var \Ls\Replication\Model\ReplHierarchy $hierarchy */
            foreach ($replHierarchyRepository->getItems() as $hierarchy) {
                $hierarchyCodes[] = [
                    'value' => $hierarchy->getData('nav_id'),
                    'label' => __($hierarchy->getData('Description'))
                ];
            }
        }else{
            $this->replicationHelper->getLogger()->debug('Store not set');
            $hierarchyCodes[] = [
                'value' => '',
                'label' => __('Please select the Store First')
            ];

        }
        return $hierarchyCodes;
    }
}
