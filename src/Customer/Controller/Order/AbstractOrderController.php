<?php

namespace Ls\Customer\Controller\Order;

use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetResponse;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetSalesByOrderIdResponse;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;

class AbstractOrderController
{
    /**
     * @var ManagerInterface
     */
    public $messageManager;

    /**
     * @var ResultFactory
     */
    public $resultRedirect;

    /** @var PageFactory */
    public $resultPageFactory;

    /**
     * @var Http $request
     */
    public $request;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @var ResultFactory
     */
    public $resultFactory;

    /**
     * @var UrlInterface
     */
    public $url;

    /**
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param OrderHelper $orderHelper
     * @param ResultFactory $result
     * @param ManagerInterface $messageManager
     * @param ResultFactory $resultFactory
     * @param UrlInterface $url
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Http $request,
        OrderHelper $orderHelper,
        ResultFactory $result,
        ManagerInterface $messageManager,
        ResultFactory $resultFactory,
        UrlInterface $url
    ) {
        $this->resultRedirect    = $result;
        $this->messageManager    = $messageManager;
        $this->request           = $request;
        $this->orderHelper       = $orderHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultFactory     = $resultFactory;
        $this->url               = $url;
    }

    /**
     * Register Values in registry
     *
     * @return ResultInterface|void
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function registerValuesInRegistry()
    {
        if ($this->request->getParam('order_id')) {
            $orderId = $this->request->getParam('order_id');
            $type    = $this->request->getParam('type');

            if (empty($type)) {
                $type = DocumentIdType::ORDER;
            }
            $response = $this->fetchAndSetCurrentOrderInRegistry($orderId, $type);

            if (empty($response) || !$this->orderHelper->isAuthorizedForOrder($response)) {
                $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $redirect->setUrl($this->url->getUrl('sales/order/history'));

                return $redirect;
            }
            $this->setHasReturnSales($response);

            if (is_array($response)) {
                $response = current($response);
            }

            $this->orderHelper->setCurrentMagOrderInRegistry($response);
        }
    }

    /**
     * Fetch and set current order in registry
     *
     * @param $orderId
     * @param $type
     * @return SalesEntry|SalesEntry[]|SalesEntryGetResponse|SalesEntryGetSalesByOrderIdResponse|ResponseInterface|null
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function fetchAndSetCurrentOrderInRegistry($orderId, $type)
    {
        $response = $this->orderHelper->fetchOrder($orderId, $type);

        if ($response) {
            $this->orderHelper->setOrderInRegistry($response);
        }

        return $response;
    }

    /**
     * Set has return sales
     *
     * @param $transactions
     * @return void
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function setHasReturnSales($transactions)
    {
        if (!is_array($transactions) && $transactions->getIdType() == DocumentIdType::ORDER) {
            if ($transactions->getPosted()) {
                $transactions = $this->orderHelper->fetchOrder(
                    $transactions->getCustomerOrderNo(),
                    DocumentIdType::RECEIPT
                );
            } else {
                $this->orderHelper->registerGivenValueInRegistry('has_return_sales', false);
                return;
            }
        }

        if (empty($transactions)) {
            $this->orderHelper->registerGivenValueInRegistry('has_return_sales', false);
            return;
        }

        if (!is_array($transactions)) {
            $transactions = [$transactions];
        }

        $hasReturnSales = $this->orderHelper->hasReturnSale($transactions);

        if ($hasReturnSales) {
            $this->orderHelper->registerGivenValueInRegistry('has_return_sales', true);
        } else {
            $this->orderHelper->registerGivenValueInRegistry('has_return_sales', false);
        }
    }
}
