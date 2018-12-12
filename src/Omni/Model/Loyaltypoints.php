<?php

namespace Ls\Omni\Model;

use Magento\Framework\DataObject;
use Ls\Omni\Helper\LoyaltyHelper;

class Loyaltypoints extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_LOYALTYPOINTS_CODE = 'loyaltypoints';

    /** @var string */
    protected $_code = self::PAYMENT_METHOD_LOYALTYPOINTS_CODE;

    /** @var string */
    protected $_formBlockType = \Ls\Omni\Block\Form\Loyaltypoints::class;

    /** @var string */
    protected $_infoBlockType = \Ls\Omni\Block\Info\Loyaltypoints::class;

    /** @var bool */
    protected $_isOffline = true;

    /** @var LoyaltyHelper */
    private $loyaltyHelper;

    /** @var \Psr\Log\LoggerInterface */
    private $debug_logger;

    /**
     * Loyaltypoints constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param LoyaltyHelper $loyaltyHelper
     * @param \Psr\Log\LoggerInterface $debug_logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        LoyaltyHelper $loyaltyHelper,
        \Psr\Log\LoggerInterface $debug_logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->loyaltyHelper = $loyaltyHelper;
        $this->debug_logger = $debug_logger;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool|mixed
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        // check if its enabled from configuration in admin.
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();

        // check if the customer is guest then return false
        if (!$quote->getCustomerId()) {
            return false;
        }
        // get member point value and check if its greater then the order amount.
        $loyaltyAmount = $this->loyaltyHelper->convertPointsIntoValues();


        $quoteValue = $quote->getBaseGrandTotal();

        // if quote value is greater then loyalty value then return false;
        if ($quoteValue > $loyaltyAmount) {
            return false;
        }

        $checkResult->setData('is_available', true);

        $this->_eventManager->dispatch(
            'payment_method_is_active',
            [
                'result' => $checkResult,
                'method_instance' => $this,
                'quote' => $quote
            ]
        );

        return $checkResult->getData('is_available');
    }
}
