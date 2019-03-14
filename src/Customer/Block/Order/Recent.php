<?php
namespace Ls\Customer\Block\Order;

use Ls\Omni\Client\Ecommerce\Entity\ArrayOfOrder;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order\Config;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Recent extends \Magento\Sales\Block\Order\Recent
{
    /**
     * @var \Ls\Omni\Helper\OrderHelper
     */
    public $orderHelper;

    /**
     * @var PriceCurrencyInterface
     */
    public $priceCurrency;

    /**
     * Recent constructor.
     * @param Context $context
     * @param CollectionFactory $orderCollectionFactory
     * @param Session $customerSession
     * @param Config $orderConfig
     * @param \Ls\Omni\Helper\OrderHelper $orderHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $orderCollectionFactory,
        Session $customerSession,
        Config $orderConfig,
        \Ls\Omni\Helper\OrderHelper $orderHelper,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->orderHelper = $orderHelper;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }

    /**
     * @return \Ls\Omni\Client\Ecommerce\Entity\Order[]
     */
    public function getOrderHistory()
    {
        $response = $this->orderHelper->getCurrentCustomerOrderHistory()->getOrder();
        if (!is_array($response)) {
            $obj = $response;
            $response = array($obj);
        }
        return $response;
    }

    /**
     * Function getFormatedPrice
     *
     * @param $amount
     *
     * @return string
     */
    public function getFormatedPrice($amount)
    {
        $price = $this->priceCurrency->format($amount, false, 2);
        return $price;
    }

    /**
     * Function getFormatedLoyaltyPoints
     *
     * @param $points
     *
     * @return string
     */
    public function getFormatedLoyaltyPoints($points)
    {
        $points = number_format((float)$points, 2, '.', '');
        return $points;
    }
    /**
     * Function getFormatedDate
     *
     * @param $date
     *
     * @return string
     */
    public function getFormatedDate($date)
    {
        // @codingStandardsIgnoreStart
        $formatedDate = new \DateTime($date);
        // @codingStandardsIgnoreEnd
        $result = $formatedDate->format('d/m/y');
        return $result;
    }
}
