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
     * @var \Magento\Checkout\Model\Session\Proxy
     */
    public $checkoutSession;

    /** @var \Magento\Framework\Message\ManagerInterface */
    public $messageManager;

    /**
     * @var Price Helper
     */
    public $priceHelper;

    /**
     * @var Loyalty Helper
     */
    public $loyaltyHelper;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $store_manager
     * @param ReplStoreRepositoryInterface $storeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SessionManagerInterface $session
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Ls\Omni\Helper\LoyaltyHelper $loyaltyHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */

    public function __construct(
        Context $context,
        StoreManagerInterface $store_manager,
        ReplStoreRepositoryInterface $storeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SessionManagerInterface $session,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        LoyaltyHelper $loyaltyHelper,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository
    )
    {
        $this->storeManager = $store_manager;
        $this->storeRepository = $storeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->session = $session;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->priceHelper = $priceHelper;
        $this->cartRepository = $cartRepository;
        $this->loyaltyHelper = $loyaltyHelper;

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
     * @param $giftCardAmount
     * @param $loyaltyPoints
     * @param $basketData
     * @return float|int
     */
    public function getOrderBalance($giftCardAmount, $loyaltyPoints, $basketData)
    {
        try {
            $loyaltyAmount = $this->loyaltyHelper->getPointRate() * $loyaltyPoints;
            if (!empty($basketData)) {
                $totalAmount = $basketData->getTotalAmount();
                return $totalAmount - $giftCardAmount - $loyaltyAmount;
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * @param $giftCardNo
     * @param $giftCardAmount
     * @param $loyaltyPoints
     * @param $basketData
     * @return bool
     */
    public function orderBalanceCheck($giftCardNo, $giftCardAmount, $loyaltyPoints, $basketData)
    {
        try {
            $loyaltyAmount = $this->loyaltyHelper->getPointRate() * $loyaltyPoints;
            $cartId = $this->checkoutSession->getQuoteId();
            $quote = $this->cartRepository->get($cartId);
            if (!empty($basketData) && is_object($basketData)) {
                $totalAmount = $basketData->getTotalAmount();
                $discountAmount = $basketData->getTotalDiscount();
                $combinedTotalLoyalGiftCard = $giftCardAmount + $loyaltyAmount;
                $combinedDiscountPaymentamount = $discountAmount + $combinedTotalLoyalGiftCard;
                if ($loyaltyAmount > $totalAmount) {
                    $quote->setLsPointsSpent(0);
                    $this->cartRepository->save($quote);
                    $this->messageManager->addErrorMessage(
                        __(
                            'The loyalty points "%1" are not valid.',
                            $loyaltyPoints
                        )
                    );
                } else if ($giftCardAmount > $totalAmount) {
                    $quote->setLsGiftCardAmountUsed(0);
                    $quote->setLsGiftCardNo(null);
                    $quote->collectTotals();
                    $this->cartRepository->save($quote);
                    $this->messageManager->addErrorMessage(
                        __(
                            'The gift card amount "%1" is not valid.',
                            $this->priceHelper->currency($giftCardAmount, true, false)
                        )
                    );
                } else if ($combinedTotalLoyalGiftCard > $totalAmount) {
                    $quote->setLsPointsSpent(0);
                    $quote->setLsGiftCardAmountUsed(0);
                    $quote->setLsGiftCardNo(null);
                    $quote->collectTotals();
                    $this->cartRepository->save($quote);
                    $this->messageManager->addErrorMessage(
                        __(
                            'The gift card amount "%1" and loyalty points ' . $loyaltyPoints . ' are not valid.',
                            $this->priceHelper->currency($giftCardAmount, true, false)
                        )
                    );
                } else if ($combinedDiscountPaymentamount > $totalAmount) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

}
