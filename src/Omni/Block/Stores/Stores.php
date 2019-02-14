<?php

namespace Ls\Omni\Block\Stores;

use \Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use \Ls\Omni\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Ls\Core\Model\LSR;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Stores
 * @package Ls\Omni\Block\Stores
 */
class Stores extends Template
{
    /**
     * @var PageFactory
     */
    public $resultPageFactory;
    /**
     * @var CollectionFactory
     */
    public $replStoreFactory;
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;
    /**
     * @var SessionManagerInterface
     */
    public $session;
    /**
     * @var Data
     */
    public $storeHoursHelper;
    /**
     * @var Data
     */
    public $logger;

    /**
     * Stores constructor.
     * @param Template\Context $context
     * @param PageFactory $resultPageFactory
     * @param CollectionFactory $replStoreCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param SessionManagerInterface $session
     * @param Data $storeHousHelper
     */
    public function __construct(
        Template\Context $context,
        PageFactory $resultPageFactory,
        CollectionFactory $replStoreCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        SessionManagerInterface $session,
        Data $storeHousHelper,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->replStoreFactory = $replStoreCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->storeHoursHelper = $storeHousHelper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return \Ls\Replication\Model\ResourceModel\ReplStore\Collection
     */
    public function getStores()
    {
        try {
            $collection = $this->replStoreFactory->create()->addFieldToFilter('IsDeleted', 0);
            return $collection;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param $storeId
     * @return array
     */
    public function getStoreHours($storeId)
    {
        try {
            $storeHours = $this->storeHoursHelper->getStoreHours($storeId);
            return $storeHours;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @return mixed
     */
    public function getStoreMapKey()
    {
        try {
            return $this->scopeConfig->getValue(
                LSR::SC_CLICKCOLLECT_GOOGLE_APIKEY,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
