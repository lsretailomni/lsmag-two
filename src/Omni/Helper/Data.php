<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use \Ls\Replication\Api\ReplStoreRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use \Ls\Omni\Client\Ecommerce\Operation\StoreGetById;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\BasketHelper;
use \Magento\Framework\Session\SessionManagerInterface;

/**
 * Class Data
 * @package Ls\Omni\Helper
 */
class Data extends AbstractHelper
{
    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    public $config;

    /** @var ReplStoreRepositoryInterface */
    public $storeRepository;

    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /**
     * @var SessionManagerInterface
     */
    public $session;

    /**
     * @var Loyalty Helper
     */
    public $loyaltyHelper;


    /**
     * @var Basket Helper
     */
    public $basketHelper;

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
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SessionManagerInterface $session,
        LoyaltyHelper $loyaltyHelper,
        BasketHelper $basketHelper
    )
    {
        $this->storeManager = $store_manager;
        $this->storeRepository = $storeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->session = $session;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->basketHelper = $basketHelper;

        $this->config = $context->getScopeConfig();
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
            // @codingStandardsIgnoreLine
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

    /**
     * @param $value
     */
    public function setValue($value)
    {
        $this->session->start();
        $this->session->setMessage($value);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        $this->session->start();
        return $this->session->getMessage();
    }

    /**
     * @return mixed
     */
    public function unSetValue()
    {
        $this->session->start();
        return $this->session->unsMessage();
    }

    /**
     * @param $baseSubTotal
     * @param $giftCardAmount
     * @param $loyaltyAmount
     * @return mixed
     */
    public function getOrderBalance($giftCardAmount, $loyaltyPoints)
    {
        try {
            $loyaltyAmount = $this->loyaltyHelper->getPointRate() * $loyaltyPoints;
            $basketData = $this->basketHelper->getBasketSessionValue();
            if (!empty($basketData)) {
                $totalAmount = $basketData->getTotalAmount();
                return $totalAmount - $giftCardAmount - $loyaltyAmount;
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }
}
