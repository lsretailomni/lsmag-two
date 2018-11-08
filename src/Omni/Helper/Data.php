<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Ls\Replication\Api\ReplStoreRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Data extends AbstractHelper
{
    /** @var StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $_config;

    /** @var ReplStoreRepositoryInterface */
    protected $storeRepository;

    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $store_manager
     * @param ReplStoreRepositoryInterface $storeRepository
     */

    public function __construct(
        Context $context,
        StoreManagerInterface $store_manager,
        ReplStoreRepositoryInterface $storeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->_storeManager = $store_manager;
        $this->storeRepository = $storeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_config = $context->getScopeConfig();
        parent::__construct($context);
    }


    /**
     * @param $storeId
     * @return mixed
     */
    public function getStoreNameById($storeId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('nav_id', $storeId, 'eq')->create();
        $stores = $this->storeRepository->getList($searchCriteria)->getItems();
        foreach ($stores as $store) {
                return $store->getData('Name');
        }
        return "Sorry! No store found with ID : " . $storeId;
    }
}
