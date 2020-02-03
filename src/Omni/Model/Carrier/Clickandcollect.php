<?php

namespace Ls\Omni\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Clickandcollect
 * @package Ls\Omni\Model\Carrier
 */
class Clickandcollect extends AbstractCarrier implements CarrierInterface
{

    /** @var string */
    // @codingStandardsIgnoreLine
    public $_code = 'clickandcollect';

    /** @var bool */
    // @codingStandardsIgnoreLine
    public $_isFixed = true;

    /** @var ResultFactory */
    public $rateResultFactory;

    /** @var MethodFactory */
    public $rateMethodFactory;

    /**
     * Clickandcollect constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => __($this->getConfigData('name'))];
    }

    /**
     * @param RateRequest $request
     * @return bool|DataObject|Result|null
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return false;
        }

        /** @var Result $result */
        $result = $this->rateResultFactory->create();

        $shippingPrice = $this->getConfigData('price');

        /** @var Method $method */
        $method = $this->rateMethodFactory->create()
            ->setCarrier($this->getCarrierCode())
            ->setCarrierTitle($this->getConfigData('title'))
            ->setMethod($this->getCarrierCode())
            ->setMethodTitle($this->getConfigData('name'))
            ->setPrice($shippingPrice)
            ->setCost($shippingPrice);

        $result->append($method);

        return $result;
    }
}
