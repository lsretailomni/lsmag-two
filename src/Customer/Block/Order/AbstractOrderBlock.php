<?php

namespace Ls\Customer\Block\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data as DataHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\OrderRepository;

class AbstractOrderBlock extends Template
{
    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /** @var LSR $lsr */
    public $lsr;

    /**
     * @var DataHelper
     */
    public $dataHelper;

    /**
     * @var PriceHelper
     */
    public $priceHelper;

    /**
     * @var OrderRepository
     */
    public $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    public $searchCriteriaBuilder;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var CountryFactory
     */
    public $countryFactory;

    /**
     * @param Context $context
     * @param PriceCurrencyInterface $priceCurrency
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSR $lsr
     * @param OrderHelper $orderHelper
     * @param DataHelper $dataHelper
     * @param PriceHelper $priceHelper
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerSession $customerSession
     * @param CountryFactory $countryFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        PriceCurrencyInterface $priceCurrency,
        LoyaltyHelper $loyaltyHelper,
        LSR $lsr,
        OrderHelper $orderHelper,
        DataHelper $dataHelper,
        PriceHelper $priceHelper,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerSession $customerSession,
        CountryFactory $countryFactory,
        array $data = []
    ) {
        $this->priceCurrency         = $priceCurrency;
        $this->loyaltyHelper         = $loyaltyHelper;
        $this->lsr                   = $lsr;
        $this->orderHelper           = $orderHelper;
        $this->dataHelper            = $dataHelper;
        $this->priceHelper           = $priceHelper;
        $this->orderRepository       = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerSession       = $customerSession;
        $this->countryFactory        = $countryFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @param $all
     * @return false|mixed|null
     */
    public function getOrder($all = false)
    {
        if ($this->getData('current_order')) {
            return $this->getData('current_order');
        }

        return $this->orderHelper->getOrder($all);
    }

    /**
     * Get magento order
     *
     * @return mixed
     */
    public function getMagOrder()
    {
        return $this->orderHelper->getGivenValueFromRegistry('current_mag_order');
    }
}
