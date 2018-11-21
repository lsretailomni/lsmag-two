<?php

namespace Ls\Omni\Block\Stores;

use Ls\Omni\Client\Ecommerce\Operation\StoreGetById;
use Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Setup\Exception;
use Magento\Store\Model\ScopeInterface;
use \Magento\Framework\Session\SessionManagerInterface;

class Stores extends Template
{
    protected $resultPageFactory;
    protected  $_replStoreFactory;
    protected $scopeConfig;
    protected $session;

    public function __construct(
        Template\Context $context,
        PageFactory $resultPageFactory,
        CollectionFactory $replStoreCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        SessionManagerInterface $session
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_replStoreFactory = $replStoreCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
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

    public function setValue($value){
        $this->session->start();
        $this->session->setMessage($value);
    }

    public function getValue(){
        $this->session->start();
        return $this->session->getMessage();
    }

    public function unSetValue(){
        $this->session->start();
        return $this->session->unsMessage();
    }

    public function getStoreHours($storeId) {
        try {
            $storeResults=array();
            $request = new StoreGetById();
            $request->getOperationInput()->setStoreId($storeId);
            if(empty($this->getValue())) {
                $storeResults = $request->execute()->getResult()->getStoreHours()->getStoreHours();
                $this->setValue($storeResults);
            }
            else {
                $storeResults=$this->getValue();
            }
            $counter = 0;
            $storeHours = array();
            foreach ($storeResults as $r) {
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
            //TODO replace this variable with proper constant declared in LSR Model
            $this->_scopeConfig->getValue('omni_clickandcollect/general/maps_api_key' , \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        }
        catch(Exception $e){

        }

    }

}