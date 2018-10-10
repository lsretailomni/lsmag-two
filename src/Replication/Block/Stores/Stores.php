<?php

namespace Ls\Replication\Block\Stores;

use Ls\Omni\Client\Ecommerce\Entity\ArrayOfStoreHours;
use Ls\Omni\Client\Ecommerce\Entity\ReplEcommItems;
use Ls\Omni\Client\Ecommerce\Entity\StoreHours;
use Ls\Omni\Client\Ecommerce\Operation\StoreGetById;
use Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Setup\Exception;
use Magento\Store\Model\ScopeInterface;
use Zend\Code\Generator\MethodGenerator;
use Ls\Core\Model\LSR;

class Stores extends Template
{
    protected $resultPageFactory;
    protected  $_replStoreFactory;
    protected $scopeConfig;

    public function __construct(
        Template\Context $context,
        PageFactory $resultPageFactory,
        CollectionFactory $replStoreCollectionFactory,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_replStoreFactory = $replStoreCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function getStores()
    {
        try {
            $collection = $this->_replStoreFactory->create()->addFieldToFilter('IsDeleted',0);

            return $collection;
        }
        catch(Exception $e){

        }

    }

    public function getStoreHours($storeId) {
        try {
            $request = new StoreGetById();
            $request->getOperationInput()->setStoreId($storeId);
            $result = $request->execute()->getResult()->getStoreHours()->getStoreHours();
            $counter = 0;
            $storeHours = array();
            foreach ($result as $r) {
                $storeHours[$counter]['openhours'] = date('h:i A', strtotime($r->getOpenFrom()));
                $storeHours[$counter]['closedhours'] = date('h:i A', strtotime($r->getOpenTo()));
                $storeHours[$counter]['day'] = $r->getNameOfDay();
                $counter++;
            }
            return $storeHours;
        }
        catch (Exception $e ){

        }
    }
    public function getStoreMapKey()
    {
        try {
            $storeScope = ScopeInterface::SCOPE_STORE;
            $this->_scopeConfig->getValue('omni_clickandcollect/general/maps_api_key' , \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        }
        catch(Exception $e){

        }

    }

}