<?php

namespace Ls\Omni\Model\Payment;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Block\Form;
use Magento\Payment\Block\Info;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;

/**
 * Class PayStore
 * @package Ls\Omni\Model\Payment
 */
class PayStore extends AbstractExtensibleModel implements
    MethodInterface,
    PaymentMethodInterface
{
    const CODE = 'ls_payment_method_pay_at_store';

    /**
     * @var string
     */
    public $code = self::CODE;

    /**
     * Availability option
     *
     * @var bool
     */
    public $isOffline = true;

    /**
     * @var string
     */
    public $formBlockType = Form::class;

    /**
     * @var string
     */
    public $infoBlockType = Info::class;

    /**
     * @var bool
     */
    public $isGateway = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    public $canOrder = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    public $canAuthorize = false;

    /**
     * @var bool
     */
    public $canCapture = false;

    /**
     * @var bool
     */
    public $canCapturePartial = false;

    /**
     * @var bool
     */
    public $canCaptureOnce = false;

    /**
     * @var bool
     */
    public $canRefund = false;

    /**
     * @var bool
     */
    public $canRefundInvoicePartial = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    public $canVoid = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    public $canUseInternal = true;

    /**
     * @var bool
     */
    public $canUseCheckout = true;

    /**
     * @var bool
     */
    public $isInitializeNeeded = false;

    /**
     * @var bool
     */
    public $canFetchTransactionInfo = false;

    /**
     * @var bool
     */
    public $canReviewPayment = false;


    /**
     * @var bool
     */
    public $canCancelInvoice = false;

    /**
     * @var Data
     */
    public $paymentData;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var DirectoryHelper
     */
    private $directory;

    /**
     * @var $dataObject
     */
    public $dataObject;

    /**
     * PayStore constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param DataObject $dataObject
     * @param Logger $logger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param DirectoryHelper|null $directory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        DataObject $dataObject,
        Logger $logger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryHelper $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->paymentData = $paymentData;
        $this->scopeConfig = $scopeConfig;
        $this->dataObject  = $dataObject;
        $this->logger      = $logger;
        $this->directory   = $directory;
        $this->initializeData($data);
    }

    /**
     * Initializes injected data
     *
     * @param array $data
     * @return void
     */
    public function initializeData($data = [])
    {
        if (!empty($data['formBlockType'])) {
            $this->formBlockType = $data['formBlockType'];
        }
    }

    /**
     * @param int $storeId
     */
    public function setStore($storeId)
    {
        $this->setData('store', (int)$storeId);
    }

    /**
     * @return int|mixed
     */
    public function getStore()
    {
        return $this->getData('store');
    }

    /**
     * @return bool
     */
    public function canOrder()
    {
        return $this->canOrder;
    }

    /**
     * @return bool
     */
    public function canAuthorize()
    {
        return $this->canAuthorize;
    }

    /**
     * @return bool
     */
    public function canCapture()
    {
        return $this->canCapture;
    }

    /**
     * @return bool
     */
    public function canCapturePartial()
    {
        return $this->canCapturePartial;
    }

    /**
     * @return bool
     */
    public function canCaptureOnce()
    {
        return $this->canCaptureOnce;
    }

    /**
     * @return bool
     */
    public function canRefund()
    {
        return $this->canRefund;
    }

    /**
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        return $this->canRefundInvoicePartial;
    }

    /**
     * @return bool
     */
    public function canVoid()
    {
        return $this->canVoid;
    }

    /**
     * @return bool
     */
    public function canUseInternal()
    {
        return $this->canUseInternal;
    }

    /**
     * @return bool
     */
    public function canUseCheckout()
    {
        return $this->canUseCheckout;
    }

    /**
     * @return bool
     */
    public function canEdit()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canFetchTransactionInfo()
    {
        return $this->canFetchTransactionInfo;
    }

    /**
     * @param InfoInterface $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        return [];
    }

    /**
     * @return bool
     */
    public function isGateway()
    {
        return $this->isGateway;
    }

    /**
     * @return bool
     */
    public function isOffline()
    {
        return $this->isOffline;
    }

    /**
     * @return bool
     */
    public function isInitializeNeeded()
    {
        return $this->isInitializeNeeded;
    }

    /**
     * @param string $country
     * @return bool
     * @throws LocalizedException
     */
    public function canUseForCountry($country)
    {
        /*
        for specific country, the flag will set up as 1
        */
        if ($this->getConfigData('allowspecific') == 1) {
            $availableCountries = explode(',', $this->getConfigData('specificcountry'));
            if (!in_array($country, $availableCountries)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        if (empty($this->code)) {
            $this->logger->debug('We cannot retrieve the payment method code.');
        }
        return $this->code;
    }

    /**
     * @return string
     */
    public function getFormBlockType()
    {
        return $this->formBlockType;
    }

    /**
     * @return string
     */
    public function getInfoBlockType()
    {
        return $this->infoBlockType;
    }

    /**
     * @return InfoInterface|mixed
     */
    public function getInfoInstance()
    {
        $instance = $this->getData('info_instance');
        if (!$instance instanceof InfoInterface) {
            $this->logger->debug('We cannot retrieve the payment information object instance');
        }
        return $instance;
    }

    /**
     * @param InfoInterface $info
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->setData('info_instance', $info);
    }

    /**
     * @return $this|MethodInterface
     * @throws LocalizedException
     */
    public function validate()
    {
        /**
         * to validate payment method is allowed for billing country or not
         */
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Payment) {
            $billingCountry = $paymentInfo->getOrder()->getBillingAddress()->getCountryId();
        } else {
            $billingCountry = $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
        }
        $billingCountry = $billingCountry ?: $this->directory->getDefaultCountry();
        if (!$this->canUseForCountry($billingCountry)) {
            $errorMsg = __('Selected Billing Country is not supported.');
            throw new \Magento\Framework\Exception\LocalizedException($errorMsg);
        }
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|MethodInterface
     */
    public function order(InfoInterface $payment, $amount)
    {

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|MethodInterface
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|MethodInterface
     */
    public function capture(InfoInterface $payment, $amount)
    {
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|MethodInterface
     */
    public function refund(InfoInterface $payment, $amount)
    {
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @return $this|MethodInterface
     */
    public function cancel(InfoInterface $payment)
    {
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @return $this|MethodInterface
     */
    public function void(InfoInterface $payment)
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function canReviewPayment()
    {
        return $this->canReviewPayment;
    }

    /**
     * @param InfoInterface $payment
     * @return bool|false
     */
    public function acceptPayment(InfoInterface $payment)
    {

        return false;
    }

    /**
     * @param InfoInterface $payment
     * @return bool|false
     */
    public function denyPayment(InfoInterface $payment)
    {
        return false;
    }

    /**
     * @return mixed|string
     * @throws LocalizedException
     */
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    /**
     * @param string $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('order_place_redirect_url' === $field) {
            return $this->getOrderPlaceRedirectUrl();
        }
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/' . $this->getCode() . '/' . $field;
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param DataObject $data
     * @return $this|MethodInterface
     */
    public function assignData(DataObject $data)
    {
        $this->_eventManager->dispatch(
            'payment_method_assign_data',
            [
                AbstractDataAssignObserver::METHOD_CODE => $this,
                AbstractDataAssignObserver::MODEL_CODE  => $this->getInfoInstance(),
                AbstractDataAssignObserver::DATA_CODE   => $data
            ]
        );

        return $this;
    }

    /**
     * @param CartInterface|null $quote
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = $this->dataObject;
        $checkResult->setData('is_available', true);

        // for future use in observers
        $this->_eventManager->dispatch(
            'payment_method_is_active',
            [
                'result'          => $checkResult,
                'method_instance' => $this,
                'quote'           => $quote
            ]
        );

        return $checkResult->getData('is_available');
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return $this|MethodInterface
     */
    public function initialize($paymentAction, $stateObject)
    {
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('payment_action');
    }
}
