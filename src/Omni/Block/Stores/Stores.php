<?php

namespace Ls\Omni\Block\Stores;

use Ls\Omni\Client\Ecommerce\Entity\ReplEcommItems;
use Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Ls\Omni\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Ls\Core\Model\LSR;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Session\SessionManagerInterface;

class Stores extends Template
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var CollectionFactory
     */
    protected $_replStoreFactory;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var SessionManagerInterface
     */
    protected $session;
    /**
     * @var Data
     */
    protected $storeHoursHelper;

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
        Data $storeHousHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_replStoreFactory = $replStoreCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->storeHoursHelper = $storeHousHelper;
        parent::__construct($context);
    }

    /**
     * @return \Ls\Replication\Model\ResourceModel\ReplStore\Collection
     */
    public function getStores()
    {
        try {
            $collection = $this->_replStoreFactory->create()->addFieldToFilter('IsDeleted', 0);
            return $collection;
        } catch (\Exception $e) {
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
        }
    }

    /**
     * @return mixed
     */
    public function getStoreMapKey()
    {
        try {
            $storeScope = ScopeInterface::SCOPE_STORE;
            return $this->_scopeConfig->getValue(LSR::SC_CLICKCOLLECT_GOOGLE_APIKEY, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        } catch (\Exception $e) {
        }
    }
}
