<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Ls\Replication\Api\ReplStoreRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Ls\Omni\Client\Ecommerce\Entity\StoreHours;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfStoreHours;
use Ls\Omni\Client\Ecommerce\Operation\StoreGetById;
use Ls\Core\Model\LSR;

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
    ) {
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

    /**
     * @param $storeId
     * @return array
     */
    public function getStoreHours($storeId)
    {
        try {
            $storeResults = [];
            $request = new StoreGetById();
            $request->getOperationInput()->setStoreId($storeId);
            if (empty($this->getValue())) {
                $storeResults = $request->execute()->getResult()->getStoreHours()->getStoreHours();
                $this->setValue($storeResults);
            } else {
                $storeResults = $this->getValue();
            }
            $counter = 0;
            $storeHours = [];
            foreach ($storeResults as $r) {
                $storeHours[$counter]['openhours'] = date(LSR::STORE_HOURS_TIME_FORMAT, strtotime($r->getOpenFrom()));
                $storeHours[$counter]['closedhours'] = date(LSR::STORE_HOURS_TIME_FORMAT, strtotime($r->getOpenTo()));
                $storeHours[$counter]['day'] = $r->getNameOfDay();
                $counter++;
            }
            return $storeHours;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }


    public function setValue($value)
    {
        $this->session->start();
        $this->session->setMessage($value);
    }

    public function getValue()
    {
        $this->session->start();
        return $this->session->getMessage();
    }

    public function unSetValue()
    {
        $this->session->start();
        return $this->session->unsMessage();
    }
}
