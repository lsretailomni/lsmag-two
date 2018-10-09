<?php
namespace Ls\Omni\Observer;

use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Event\ObserverInterface;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Core\Model\LSR;
use Magento\Sales\Model\Order;

class OrderObserver implements ObserverInterface
{
    /** @var ContactHelper  */
    private $contactHelper;

    /** @var BasketHelper  */
    protected $basketHelper;

    /** @var OrderHelper  */
    protected $orderHelper;

    /** @var \Psr\Log\LoggerInterface  */
    protected $logger;

    /** @var \Magento\Customer\Model\Session  */
    protected $customerSession;

    /** @var \Magento\Checkout\Model\Session  */
    protected $checkoutSession;

    /** @var bool  */
    protected $watchNextSave = FALSE;

    /**
     * OrderObserver constructor.
     * @param ContactHelper $contactHelper
     * @param BasketHelper $basketHelper
     * @param OrderHelper $orderHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */

    public function __construct(
        ContactHelper $contactHelper,
        BasketHelper $basketHelper,
        OrderHelper $orderHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->contactHelper = $contactHelper;
        $this->basketHelper = $basketHelper;
        $this->orderHelper = $orderHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getData( 'order' );


        /** @var Entity\BasketCalcResponse $basketCalculation */
        $basketCalculation = $this->basketHelper->getOneListCalculation();

        $request = $this->orderHelper->prepareOrder($order, $basketCalculation);
        $response = $this->orderHelper->placeOrder($request);

        if($response){
            //delete from Omni.
            if($this->customerSession->getData(   LSR::SESSION_CART_ONELIST )){
                $onelist        =    $this->customerSession->getData(   LSR::SESSION_CART_ONELIST );

                //TODO error which Hjalti highlighted. when there is only one item in the cart and customer remove that.
                $success      =   $this->basketHelper->delete($onelist);

                //$this->logger->debug(var_export($something,true)); //exit;

                $this->customerSession->unsetData(LSR::SESSION_CART_ONELIST);

                // delete checkout session data.
                $this->basketHelper->unSetOneListCalculation();

                //remove data
            }

        }else {
            // TODO: error handling
            $this->logger->critical('Something trrible happen while placing order');
        }

        return $this;
    }
}

/*
 * Below is the response of $order->getData(); function
 * array (size=60)
  'applied_rule_ids' => string '' (length=0)
  'base_currency_code' => string 'USD' (length=3)
  'base_discount_amount' => float 0
  'base_grand_total' => float 47
  'base_discount_tax_compensation_amount' => float 0
  'base_shipping_amount' => float 5
  'base_shipping_discount_amount' => float 0
  'base_shipping_discount_tax_compensation_amnt' => float 0
  'base_shipping_incl_tax' => float 5
  'base_shipping_tax_amount' => float 0
  'base_subtotal' => float 42
  'base_subtotal_incl_tax' => float 42
  'base_tax_amount' => float 0
  'base_total_due' => float 47
  'base_to_global_rate' => float 1
  'base_to_order_rate' => float 1
  'customer_email' => string 'shantest25@gmail.com' (length=20)
  'customer_firstname' => string 'Shan' (length=4)
  'customer_group_id' => int 1
  'customer_id' => string '44' (length=2)
  'customer_is_guest' => int 0
  'customer_lastname' => string 'test25' (length=6)
  'customer_note_notify' => int 1
  'discount_amount' => float 0
  'discount_description' => string '' (length=0)
  'global_currency_code' => string 'USD' (length=3)
  'grand_total' => float 47
  'discount_tax_compensation_amount' => float 0
  'increment_id' => string '000000037' (length=9)
  'is_virtual' => int 0
  'order_currency_code' => string 'USD' (length=3)
  'quote_id' => string '89' (length=2)
  'remote_ip' => string '172.18.0.1' (length=10)
  'shipping_amount' => float 5
  'shipping_description' => string 'Flat Rate - Fixed' (length=17)
  'shipping_discount_amount' => float 0
  'shipping_discount_tax_compensation_amount' => float 0
  'shipping_incl_tax' => float 5
  'shipping_tax_amount' => float 0
  'store_currency_code' => string 'USD' (length=3)
  'store_id' => int 1
  'store_to_base_rate' => float 0
  'store_to_order_rate' => float 0
  'subtotal' => float 42
  'subtotal_incl_tax' => float 42
  'tax_amount' => float 0
  'total_due' => float 47
  'total_qty_ordered' => float 1
  'weight' => float 1
  'items' =>
    array (size=2)
      0 =>
        object(Magento\Sales\Model\Order\Item\Interceptor)[3372]
          protected '_eventPrefix' => string 'sales_order_item' (length=16)
          protected '_eventObject' => string 'item' (length=4)
          protected '_order' => null
          protected '_children' =>
            array (size=1)
              ...
          protected '_orderFactory' =>
            object(Magento\Sales\Model\OrderFactory)[916]
              ...
          protected 'productRepository' =>
            object(Magento\Catalog\Model\ProductRepository\Interceptor)[996]
              ...
          protected '_storeManager' =>
            object(Magento\Store\Model\StoreManager)[197]
              ...
          private 'serializer' (Magento\Sales\Model\Order\Item) =>
            object(Magento\Framework\Serialize\Serializer\Json)[176]
              ...
          protected 'extensionAttributesFactory' =>
            object(Magento\Framework\Api\ExtensionAttributesFactory)[890]
              ...
          protected 'extensionAttributes' => null
          protected 'customAttributeFactory' =>
            object(Magento\Framework\Api\AttributeValueFactory)[891]
              ...
          protected 'customAttributesCodes' => null
          protected 'customAttributesChanged' => boolean true
          protected '_idFieldName' => string 'item_id' (length=7)
          protected '_hasDataChanges' => boolean true
          protected '_origData' => null
          protected '_isDeleted' => boolean false
          protected '_resource' => null
          protected '_resourceCollection' => null
          protected '_resourceName' => string 'Magento\Sales\Model\ResourceModel\Order\Item' (length=44)
          protected '_collectionName' => string 'Magento\Sales\Model\ResourceModel\Order\Item\Collection' (length=55)
          protected '_cacheTag' => boolean false
          protected '_dataSaveAllowed' => boolean true
          protected '_isObjectNew' => null
          protected '_validatorBeforeSave' => null
          protected '_eventManager' =>
            object(Magento\Framework\Event\Manager\Proxy)[215]
              ...
          protected '_cacheManager' =>
            object(Magento\Framework\App\Cache\Proxy)[177]
              ...
          protected '_registry' =>
            object(Magento\Framework\Registry)[171]
              ...
          protected '_logger' =>
            object(Magento\Framework\Logger\Monolog)[123]
              ...
          protected '_appState' =>
            object(Magento\Framework\App\State)[85]
              ...
          protected '_actionValidator' =>
            object(Magento\Framework\Model\ActionValidator\RemoveAction\Proxy)[296]
              ...
          protected 'storedData' =>
            array (size=0)
              ...
          protected '_data' =>
            array (size=50)
              ...
          private 'pluginList' =>
            object(Magento\Framework\Interception\PluginList\PluginList)[180]
              ...
          private 'subjectType' => string 'Magento\Sales\Model\Order\Item' (length=30)
      1 =>
        object(Magento\Sales\Model\Order\Item\Interceptor)[3392]
          protected '_eventPrefix' => string 'sales_order_item' (length=16)
          protected '_eventObject' => string 'item' (length=4)
          protected '_order' => null
          protected '_children' =>
            array (size=0)
              ...
          protected '_orderFactory' =>
            object(Magento\Sales\Model\OrderFactory)[916]
              ...
          protected 'productRepository' =>
            object(Magento\Catalog\Model\ProductRepository\Interceptor)[996]
              ...
          protected '_storeManager' =>
            object(Magento\Store\Model\StoreManager)[197]
              ...
          private 'serializer' (Magento\Sales\Model\Order\Item) =>
            object(Magento\Framework\Serialize\Serializer\Json)[176]
              ...
          protected 'extensionAttributesFactory' =>
            object(Magento\Framework\Api\ExtensionAttributesFactory)[890]
              ...
          protected 'extensionAttributes' => null
          protected 'customAttributeFactory' =>
            object(Magento\Framework\Api\AttributeValueFactory)[891]
              ...
          protected 'customAttributesCodes' => null
          protected 'customAttributesChanged' => boolean true
          protected '_idFieldName' => string 'item_id' (length=7)
          protected '_hasDataChanges' => boolean true
          protected '_origData' => null
          protected '_isDeleted' => boolean false
          protected '_resource' => null
          protected '_resourceCollection' => null
          protected '_resourceName' => string 'Magento\Sales\Model\ResourceModel\Order\Item' (length=44)
          protected '_collectionName' => string 'Magento\Sales\Model\ResourceModel\Order\Item\Collection' (length=55)
          protected '_cacheTag' => boolean false
          protected '_dataSaveAllowed' => boolean true
          protected '_isObjectNew' => null
          protected '_validatorBeforeSave' => null
          protected '_eventManager' =>
            object(Magento\Framework\Event\Manager\Proxy)[215]
              ...
          protected '_cacheManager' =>
            object(Magento\Framework\App\Cache\Proxy)[177]
              ...
          protected '_registry' =>
            object(Magento\Framework\Registry)[171]
              ...
          protected '_logger' =>
            object(Magento\Framework\Logger\Monolog)[123]
              ...
          protected '_appState' =>
            object(Magento\Framework\App\State)[85]
              ...
          protected '_actionValidator' =>
            object(Magento\Framework\Model\ActionValidator\RemoveAction\Proxy)[296]
              ...
          protected 'storedData' =>
            array (size=0)
              ...
          protected '_data' =>
            array (size=50)
              ...
          private 'pluginList' =>
            object(Magento\Framework\Interception\PluginList\PluginList)[180]
              ...
          private 'subjectType' => string 'Magento\Sales\Model\Order\Item' (length=30)
  'status_histories' =>
    array (size=0)
      empty
  'extension_attributes' =>
    object(Magento\Sales\Api\Data\OrderExtension)[3630]
      protected '_data' =>
        array (size=1)
          'item_applied_taxes' =>
            array (size=0)
              ...
  'addresses' =>
    array (size=2)
      0 =>
        object(Magento\Sales\Model\Order\Address)[3276]
          protected 'order' =>
            object(Magento\Sales\Model\Order\Interceptor)[3358]
              ...
          protected '_eventPrefix' => string 'sales_order_address' (length=19)
          protected '_eventObject' => string 'address' (length=7)
          protected 'orderFactory' =>
            object(Magento\Sales\Model\OrderFactory)[916]
              ...
          protected 'regionFactory' =>
            object(Magento\Directory\Model\RegionFactory)[902]
              ...
          protected 'extensionAttributesFactory' =>
            object(Magento\Framework\Api\ExtensionAttributesFactory)[890]
              ...
          protected 'extensionAttributes' => null
          protected 'customAttributeFactory' =>
            object(Magento\Framework\Api\AttributeValueFactory)[891]
              ...
          protected 'customAttributesCodes' => null
          protected 'customAttributesChanged' => boolean true
          protected '_idFieldName' => string 'entity_id' (length=9)
          protected '_hasDataChanges' => boolean true
          protected '_origData' => null
          protected '_isDeleted' => boolean false
          protected '_resource' => null
          protected '_resourceCollection' => null
          protected '_resourceName' => string 'Magento\Sales\Model\ResourceModel\Order\Address' (length=47)
          protected '_collectionName' => string 'Magento\Sales\Model\ResourceModel\Order\Address\Collection' (length=58)
          protected '_cacheTag' => boolean false
          protected '_dataSaveAllowed' => boolean true
          protected '_isObjectNew' => null
          protected '_validatorBeforeSave' => null
          protected '_eventManager' =>
            object(Magento\Framework\Event\Manager\Proxy)[215]
              ...
          protected '_cacheManager' =>
            object(Magento\Framework\App\Cache\Proxy)[177]
              ...
          protected '_registry' =>
            object(Magento\Framework\Registry)[171]
              ...
          protected '_logger' =>
            object(Magento\Framework\Logger\Monolog)[123]
              ...
          protected '_appState' =>
            object(Magento\Framework\App\State)[85]
              ...
          protected '_actionValidator' =>
            object(Magento\Framework\Model\ActionValidator\RemoveAction\Proxy)[296]
              ...
          protected 'storedData' =>
            array (size=0)
              ...
          protected '_data' =>
            array (size=23)
              ...
      1 =>
        object(Magento\Sales\Model\Order\Address)[3539]
          protected 'order' =>
            object(Magento\Sales\Model\Order\Interceptor)[3358]
              ...
          protected '_eventPrefix' => string 'sales_order_address' (length=19)
          protected '_eventObject' => string 'address' (length=7)
          protected 'orderFactory' =>
            object(Magento\Sales\Model\OrderFactory)[916]
              ...
          protected 'regionFactory' =>
            object(Magento\Directory\Model\RegionFactory)[902]
              ...
          protected 'extensionAttributesFactory' =>
            object(Magento\Framework\Api\ExtensionAttributesFactory)[890]
              ...
          protected 'extensionAttributes' => null
          protected 'customAttributeFactory' =>
            object(Magento\Framework\Api\AttributeValueFactory)[891]
              ...
          protected 'customAttributesCodes' => null
          protected 'customAttributesChanged' => boolean true
          protected '_idFieldName' => string 'entity_id' (length=9)
          protected '_hasDataChanges' => boolean true
          protected '_origData' => null
          protected '_isDeleted' => boolean false
          protected '_resource' => null
          protected '_resourceCollection' => null
          protected '_resourceName' => string 'Magento\Sales\Model\ResourceModel\Order\Address' (length=47)
          protected '_collectionName' => string 'Magento\Sales\Model\ResourceModel\Order\Address\Collection' (length=58)
          protected '_cacheTag' => boolean false
          protected '_dataSaveAllowed' => boolean true
          protected '_isObjectNew' => null
          protected '_validatorBeforeSave' => null
          protected '_eventManager' =>
            object(Magento\Framework\Event\Manager\Proxy)[215]
              ...
          protected '_cacheManager' =>
            object(Magento\Framework\App\Cache\Proxy)[177]
              ...
          protected '_registry' =>
            object(Magento\Framework\Registry)[171]
              ...
          protected '_logger' =>
            object(Magento\Framework\Logger\Monolog)[123]
              ...
          protected '_appState' =>
            object(Magento\Framework\App\State)[85]
              ...
          protected '_actionValidator' =>
            object(Magento\Framework\Model\ActionValidator\RemoveAction\Proxy)[296]
              ...
          protected 'storedData' =>
            array (size=0)
              ...
          protected '_data' =>
            array (size=23)
              ...
  'shipping_method' => string 'flatrate_flatrate' (length=17)
  'payment' =>
    object(Magento\Sales\Model\Order\Payment\Interceptor)[3565]
      protected '_order' =>
        object(Magento\Sales\Model\Order\Interceptor)[3358]
          protected '_eventPrefix' => string 'sales_order' (length=11)
          protected '_eventObject' => string 'order' (length=5)
          protected '_invoices' => null
          protected '_tracks' => null
          protected '_shipments' => null
          protected '_creditmemos' => null
          protected '_relatedObjects' =>
            array (size=0)
              ...
          protected '_orderCurrency' => null
          protected '_baseCurrency' => null
          protected '_actionFlag' =>
            array (size=0)
              ...
          protected '_canSendNewEmailFlag' => boolean true
          protected 'entityType' => string 'order' (length=5)
          protected '_storeManager' =>
            object(Magento\Store\Model\StoreManager)[197]
              ...
          protected '_orderConfig' =>
            object(Magento\Sales\Model\Order\Config)[3019]
              ...
          protected 'productRepository' =>
            object(Magento\Catalog\Model\ProductRepository\Interceptor)[996]
              ...
          protected 'productListFactory' =>
            object(Magento\Catalog\Model\ResourceModel\Product\CollectionFactory)[967]
              ...
          protected '_orderItemCollectionFactory' =>
            object(Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory)[3016]
              ...
          protected '_productVisibility' =>
            object(Magento\Catalog\Model\Product\Visibility)[1222]
              ...
          protected 'invoiceManagement' =>
            object(Magento\Sales\Model\Service\InvoiceService)[2988]
              ...
          protected '_currencyFactory' =>
            object(Magento\Directory\Model\CurrencyFactory)[898]
              ...
          protected '_orderHistoryFactory' =>
            object(Magento\Sales\Model\Order\Status\HistoryFactory)[2986]
              ...
          protected '_addressCollectionFactory' =>
            object(Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory)[2984]
              ...
          protected '_paymentCollectionFactory' =>
            object(Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory)[3362]
              ...
          protected '_historyCollectionFactory' =>
            object(Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory)[555]
              ...
          protected '_invoiceCollectionFactory' =>
            object(Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory)[3303]
              ...
          protected '_shipmentCollectionFactory' =>
            object(Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory)[3359]
              ...
          protected '_memoCollectionFactory' =>
            object(Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory)[3361]
              ...
          protected '_trackCollectionFactory' =>
            object(Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory)[3360]
              ...
          protected 'salesOrderCollectionFactory' =>
            object(Magento\Sales\Model\ResourceModel\Order\CollectionFactory)[3354]
              ...
          protected 'priceCurrency' =>
            object(Magento\Directory\Model\PriceCurrency)[1046]
              ...
          protected 'timezone' =>
            object(Magento\Framework\Stdlib\DateTime\Timezone)[525]
              ...
          protected 'extensionAttributesFactory' =>
            object(Magento\Framework\Api\ExtensionAttributesFactory)[890]
              ...
          protected 'extensionAttributes' => null
          protected 'customAttributeFactory' =>
            object(Magento\Framework\Api\AttributeValueFactory)[891]
              ...
          protected 'customAttributesCodes' => null
          protected 'customAttributesChanged' => boolean true
          protected '_idFieldName' => string 'entity_id' (length=9)
          protected '_hasDataChanges' => boolean true
          protected '_origData' => null
          protected '_isDeleted' => boolean false
          protected '_resource' => null
          protected '_resourceCollection' => null
          protected '_resourceName' => string 'Magento\Sales\Model\ResourceModel\Order' (length=39)
          protected '_collectionName' => string 'Magento\Sales\Model\ResourceModel\Order\Collection' (length=50)
          protected '_cacheTag' => boolean false
          protected '_dataSaveAllowed' => boolean true
          protected '_isObjectNew' => null
          protected '_validatorBeforeSave' => null
          protected '_eventManager' =>
            object(Magento\Framework\Event\Manager\Proxy)[215]
              ...
          protected '_cacheManager' =>
            object(Magento\Framework\App\Cache\Proxy)[177]
              ...
          protected '_registry' =>
            object(Magento\Framework\Registry)[171]
              ...
          protected '_logger' =>
            object(Magento\Framework\Logger\Monolog)[123]
              ...
          protected '_appState' =>
            object(Magento\Framework\App\State)[85]
              ...
          protected '_actionValidator' =>
            object(Magento\Framework\Model\ActionValidator\RemoveAction\Proxy)[296]
              ...
          protected 'storedData' =>
            array (size=0)
              ...
          protected '_data' =>
            array (size=60)
              ...
          private 'pluginList' =>
            object(Magento\Framework\Interception\PluginList\PluginList)[180]
              ...
          private 'subjectType' => string 'Magento\Sales\Model\Order' (length=25)
          public '_eavConfig' =>
            object(Magento\Eav\Model\Config)[729]
              ...
      protected '_canVoidLookup' => null
      protected '_eventPrefix' => string 'sales_order_payment' (length=19)
      protected '_eventObject' => string 'payment' (length=7)
      protected 'transactionAdditionalInfo' =>
        array (size=0)
          empty
      protected 'creditmemoFactory' =>
        object(Magento\Sales\Model\Order\CreditmemoFactory)[3540]
          protected 'convertor' =>
            object(Magento\Sales\Model\Convert\Order)[3541]
              ...
          protected 'taxConfig' =>
            object(Magento\Tax\Model\Config)[1049]
              ...
          protected 'unserialize' => null
          private 'serializer' =>
            object(Magento\Framework\Serialize\Serializer\Json)[176]
              ...
      protected 'priceCurrency' =>
        object(Magento\Directory\Model\PriceCurrency)[1046]
          protected 'storeManager' =>
            object(Magento\Store\Model\StoreManager)[197]
              ...
          protected 'currencyFactory' =>
            object(Magento\Directory\Model\CurrencyFactory)[898]
              ...
          protected 'logger' =>
            object(Magento\Framework\Logger\Monolog)[123]
              ...
      protected 'transactionRepository' =>
        object(Magento\Sales\Model\Order\Payment\Transaction\Repository)[3551]
          private 'searchResultFactory' =>
            object(Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory)[3542]
              ...
          private 'filterBuilder' =>
            object(Magento\Framework\Api\FilterBuilder)[3543]
              ...
          private 'searchCriteriaBuilder' =>
            object(Magento\Framework\Api\SearchCriteriaBuilder)[3547]
              ...
          private 'metaData' =>
            object(Magento\Sales\Model\ResourceModel\Metadata)[3549]
              ...
          private 'sortOrderBuilder' =>
            object(Magento\Framework\Api\SortOrderBuilder)[3548]
              ...
          private 'entityStorage' =>
            object(Magento\Sales\Model\EntityStorage)[3552]
              ...
          private 'collectionProcessor' =>
            object(Magento\Framework\Api\SearchCriteria\CollectionProcessor)[803]
              ...
      protected 'transactionManager' =>
        object(Magento\Sales\Model\Order\Payment\Transaction\Manager)[3553]
          protected 'transactionRepository' =>
            object(Magento\Sales\Model\Order\Payment\Transaction\Repository)[3551]
              ...
      protected 'transactionBuilder' =>
        object(Magento\Sales\Model\Order\Payment\Transaction\Builder)[3554]
          protected 'payment' => null
          protected 'order' => null
          protected 'document' => null
          protected 'failSafe' => boolean false
          protected 'message' => null
          protected 'transactionId' => null
          protected 'transactionAdditionalInfo' =>
            array (size=0)
              ...
          protected 'transactionRepository' =>
            object(Magento\Sales\Model\Order\Payment\Transaction\Repository)[3551]
              ...
      protected 'orderPaymentProcessor' =>
        object(Magento\Sales\Model\Order\Payment\Processor)[3564]
          protected 'authorizeOperation' =>
            object(Magento\Sales\Model\Order\Payment\Operations\AuthorizeOperation)[3557]
              ...
          protected 'captureOperation' =>
            object(Magento\Sales\Model\Order\Payment\Operations\CaptureOperation)[3559]
              ...
          protected 'orderOperation' =>
            object(Magento\Sales\Model\Order\Payment\Operations\OrderOperation)[3561]
              ...
          protected 'registerCaptureNotification' =>
            object(Magento\Sales\Model\Order\Payment\Operations\RegisterCaptureNotificationOperation)[3563]
              ...
      protected 'orderRepository' =>
        object(Magento\Sales\Model\OrderRepository\Interceptor)[572]
          protected 'metadata' =>
            object(Magento\Sales\Model\ResourceModel\Metadata)[573]
              ...
          protected 'searchResultFactory' =>
            object(Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory)[579]
              ...
          private 'orderExtensionFactory' (Magento\Sales\Model\OrderRepository) =>
            object(Magento\Sales\Api\Data\OrderExtensionFactory)[574]
              ...
          private 'shippingAssignmentBuilder' (Magento\Sales\Model\OrderRepository) => null
          private 'collectionProcessor' (Magento\Sales\Model\OrderRepository) =>
            object(Magento\Framework\Api\SearchCriteria\CollectionProcessor)[803]
              ...
          protected 'registry' =>
            array (size=0)
              ...
          private 'pluginList' =>
            object(Magento\Framework\Interception\PluginList\PluginList)[180]
              ...
          private 'subjectType' => string 'Magento\Sales\Model\OrderRepository' (length=35)
      private 'orderStateResolver' (Magento\Sales\Model\Order\Payment) => null
      private 'creditmemoManager' (Magento\Sales\Model\Order\Payment) =>
        object(Magento\Sales\Model\Service\CreditmemoService)[3583]
          protected 'creditmemoRepository' =>
            object(Magento\Sales\Model\Order\CreditmemoRepository)[2987]
              ...
          protected 'commentRepository' =>
            object(Magento\Sales\Model\Order\Creditmemo\CommentRepository)[3570]
              ...
          protected 'searchCriteriaBuilder' =>
            object(Magento\Framework\Api\SearchCriteriaBuilder)[3574]
              ...
          protected 'filterBuilder' =>
            object(Magento\Framework\Api\FilterBuilder)[3575]
              ...
          protected 'creditmemoNotifier' =>
            object(Magento\Sales\Model\Order\CreditmemoNotifier)[3582]
              ...
          protected 'priceCurrency' =>
            object(Magento\Directory\Model\PriceCurrency)[1046]
              ...
          protected 'eventManager' =>
            object(Magento\Framework\Event\Manager\Proxy)[215]
              ...
          private 'resource' => null
          private 'refundAdapter' => null
          private 'orderRepository' => null
          private 'invoiceRepository' => null
      protected 'additionalInformation' =>
        array (size=1)
          'method_title' => string 'Loyalty Points' (length=14)
      protected 'paymentData' =>
        object(Magento\Payment\Helper\Data)[511]
          protected '_paymentConfig' =>
            object(Magento\Payment\Model\Config)[513]
              ...
          protected '_layout' =>
            object(Magento\Framework\View\Layout\Interceptor)[402]
              ...
          protected '_methodFactory' =>
            object(Magento\Payment\Model\Method\Factory)[526]
              ...
          protected '_appEmulation' =>
            object(Magento\Store\Model\App\Emulation)[522]
              ...
          protected '_initialConfig' =>
            object(Magento\Framework\App\Config\Initial)[239]
              ...
          protected '_moduleName' => null
          protected '_request' =>
            object(Magento\Framework\App\Request\Http\Proxy)[214]
              ...
          protected '_moduleManager' =>
            object(Magento\Framework\Module\Manager)[213]
              ...
          protected '_logger' =>
            object(Magento\Framework\Logger\Monolog)[123]
              ...
          protected '_urlBuilder' =>
            object(Magento\Framework\Url)[209]
              ...
          protected '_httpHeader' =>
            object(Magento\Framework\HTTP\Header)[185]
              ...
          protected '_eventManager' =>
            object(Magento\Framework\Event\Manager\Proxy)[215]
              ...
          protected '_remoteAddress' =>
            object(Magento\Framework\HTTP\PhpEnvironment\RemoteAddress)[183]
              ...
          protected 'urlEncoder' =>
            object(Magento\Framework\Url\Encoder)[199]
              ...
          protected 'urlDecoder' =>
            object(Magento\Framework\Url\Decoder)[211]
              ...
          protected 'scopeConfig' =>
            object(Magento\Framework\App\Config)[120]
              ...
          protected '_cacheConfig' =>
            object(Magento\Framework\Cache\Config)[187]
              ...
      protected 'encryptor' =>
        object(Magento\Framework\Encryption\Encryptor)[250]
          private 'hashVersionMap' =>
            array (size=2)
              ...
          private 'passwordHashMap' =>
            array (size=3)
              ...
          protected 'cipher' => int 2
          protected 'keyVersion' => int 0
          protected 'keys' =>
            array (size=1)
              ...
          public 'random' =>
            object(Magento\Framework\Math\Random)[222]
              ...
      protected 'extensionAttributesFactory' =>
        object(Magento\Framework\Api\ExtensionAttributesFactory)[890]
          protected 'objectManager' =>
            object(Magento\Framework\App\ObjectManager)[43]
              ...
          private 'classInterfaceMap' =>
            array (size=13)
              ...
      protected 'extensionAttributes' => null
      protected 'customAttributeFactory' =>
        object(Magento\Framework\Api\AttributeValueFactory)[891]
          protected '_objectManager' =>
            object(Magento\Framework\App\ObjectManager)[43]
              ...
      protected 'customAttributesCodes' => null
      protected 'customAttributesChanged' => boolean true
      protected '_idFieldName' => string 'entity_id' (length=9)
      protected '_hasDataChanges' => boolean true
      protected '_origData' => null
      protected '_isDeleted' => boolean false
      protected '_resource' => null
      protected '_resourceCollection' => null
      protected '_resourceName' => string 'Magento\Sales\Model\ResourceModel\Order\Payment' (length=47)
      protected '_collectionName' => string 'Magento\Sales\Model\ResourceModel\Order\Payment\Collection' (length=58)
      protected '_cacheTag' => boolean false
      protected '_dataSaveAllowed' => boolean true
      protected '_isObjectNew' => null
      protected '_validatorBeforeSave' => null
      protected '_eventManager' =>
        object(Magento\Framework\Event\Manager\Proxy)[215]
          protected '_objectManager' =>
            object(Magento\Framework\App\ObjectManager)[43]
              ...
          protected '_instanceName' => string '\Magento\Framework\Event\Manager' (length=32)
          protected '_subject' =>
            object(Magento\Framework\Event\Manager)[11]
              ...
          protected '_isShared' => boolean true
          protected '_events' =>
            array (size=0)
              ...
          protected '_invoker' => null
          protected '_eventConfig' => null
      protected '_cacheManager' =>
        object(Magento\Framework\App\Cache\Proxy)[177]
          protected '_objectManager' =>
            object(Magento\Framework\App\ObjectManager)[43]
              ...
          protected '_cache' => null
      protected '_registry' =>
        object(Magento\Framework\Registry)[171]
          private '_registry' =>
            array (size=0)
              ...
      protected '_logger' =>
        object(Magento\Framework\Logger\Monolog)[123]
          protected 'name' => string 'main' (length=4)
          protected 'handlers' =>
            array (size=2)
              ...
          protected 'processors' =>
            array (size=0)
              ...
          protected 'microsecondTimestamps' => boolean true
      protected '_appState' =>
        object(Magento\Framework\App\State)[85]
          protected '_appMode' => string 'developer' (length=9)
          protected '_isDownloader' => boolean false
          protected '_updateMode' => boolean false
          protected '_configScope' =>
            object(Magento\Framework\Config\Scope)[45]
              ...
          protected '_areaCode' => string 'webapi_rest' (length=11)
          protected '_isAreaCodeEmulated' => boolean false
          private 'areaList' =>
            object(Magento\Framework\App\AreaList)[137]
              ...
      protected '_actionValidator' =>
        object(Magento\Framework\Model\ActionValidator\RemoveAction\Proxy)[296]
          protected '_objectManager' =>
            object(Magento\Framework\App\ObjectManager)[43]
              ...
          protected '_instanceName' => string '\Magento\Framework\Model\ActionValidator\RemoveAction' (length=53)
          protected '_subject' =>
            object(Magento\Framework\Model\ActionValidator\RemoveAction\Allowed)[2407]
              ...
          protected '_isShared' => boolean true
          protected 'registry' => null
          protected 'protectedModels' => null
      protected 'storedData' =>
        array (size=0)
          empty
      protected '_data' =>
        array (size=21)
          'method' => string 'loyaltypoints' (length=13)
          'additional_data' => null
          'additional_information' =>
            array (size=1)
              ...
          'po_number' => null
          'cc_type' => null
          'cc_number_enc' => null
          'cc_last_4' => null
          'cc_owner' => null
          'cc_exp_month' => null
          'cc_exp_year' => string '0' (length=1)
          'cc_ss_issue' => null
          'cc_ss_start_month' => string '0' (length=1)
          'cc_ss_start_year' => string '0' (length=1)
          'cc_number' => null
          'cc_cid' => null
          'parent_id' => null
          'amount_ordered' => float 47
          'base_amount_ordered' => float 47
          'shipping_amount' => float 5
          'base_shipping_amount' => float 5
          'method_instance' =>
            object(Ls\Omni\Model\Loyaltypoints\Interceptor)[3406]
              ...
      private 'pluginList' =>
        object(Magento\Framework\Interception\PluginList\PluginList)[180]
          protected '_inherited' =>
            array (size=95)
              ...
          protected '_processed' =>
            array (size=60)
              ...
          protected '_omConfig' =>
            object(Magento\Framework\Interception\ObjectManager\Config\Compiled)[26]
              ...
          protected '_relations' =>
            object(Magento\Framework\ObjectManager\Relations\Runtime)[22]
              ...
          protected '_definitions' =>
            object(Magento\Framework\Interception\Definition\Runtime)[41]
              ...
          protected '_classDefinitions' =>
            object(Magento\Framework\ObjectManager\Definition\Runtime)[20]
              ...
          protected '_objectManager' =>
            object(Magento\Framework\App\ObjectManager)[43]
              ...
          protected '_pluginInstances' =>
            array (size=21)
              ...
          private 'logger' => null
          private 'serializer' =>
            object(Magento\Framework\Serialize\Serializer\Serialize)[57]
              ...
          protected '_configScope' =>
            object(Magento\Framework\Config\Scope)[45]
              ...
          protected '_reader' =>
            object(Magento\Framework\ObjectManager\Config\Reader\Dom\Proxy)[46]
              ...
          protected '_cache' =>
            object(Magento\Framework\App\Interception\Cache\CompiledConfig)[169]
              ...
          protected '_cacheId' => string 'plugin-list' (length=11)
          protected '_scopePriorityScheme' =>
            array (size=2)
              ...
          protected '_loadedScopes' =>
            array (size=2)
              ...
          protected 'cacheTags' =>
            array (size=0)
              ...
          protected '_data' =>
            array (size=144)
              ...
          private 'reader' (Magento\Framework\Config\Data) => null
          private 'cache' (Magento\Framework\Config\Data) => null
          private 'cacheId' (Magento\Framework\Config\Data) => null
          private 'serializer' (Magento\Framework\Config\Data\Scoped) =>
            object(Magento\Framework\Serialize\Serializer\Serialize)[57]
              ...
          private 'serializer' (Magento\Framework\Config\Data) => null
      private 'subjectType' => string 'Magento\Sales\Model\Order\Payment' (length=33)
  'customer_middlename' => null
  'pickup_date' => null
  'gift_message_id' => null
  'state' => string 'new' (length=3)
  'status' => string 'pending' (length=7)
 */
