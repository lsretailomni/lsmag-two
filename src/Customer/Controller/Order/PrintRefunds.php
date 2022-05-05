<?php

namespace Ls\Customer\Controller\Order;

use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetResponse;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller being used for customer order print refunds
 */
class PrintRefunds extends Action
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
     * @var Registry
     */
    public $registry;

    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param OrderHelper $orderHelper
     * @param Registry $registry
     * @param ResultFactory $result
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Http $request,
        OrderHelper $orderHelper,
        Registry $registry,
        ResultFactory $result,
        ManagerInterface $messageManager
    ) {
        $this->resultRedirect    = $result;
        $this->messageManager    = $messageManager;
        $this->request           = $request;
        $this->registry          = $registry;
        $this->orderHelper       = $orderHelper;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     *
     * @return \Magento\Framework\App\ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $response = null;

        if ($this->request->getParam('order_id')) {
            $orderId  = $this->request->getParam('order_id');
            $type    = $this->request->getParam('type');

            if (empty($type)) {
                $type = DocumentIdType::ORDER;
            }

            $response = $this->setCurrentOrderInRegistry($orderId, $type);

            if ($response === null || !$this->orderHelper->isAuthorizedForReturnOrder($response)) {
                return $this->_redirect('sales/order/history/');
            }

            $this->setCurrentMagOrderReturnInRegistry($response);

            $this->registry->register('current_invoice_option', false);
            $this->registry->register('current_shipment_option', false);
            $this->registry->register('hide_shipping_links', true);
        }
        return $this->resultPageFactory->create();
    }

    /**
     * Set currentOrder into registry
     *
     * @param $orderId
     * @param $type
     * @return SalesEntry|SalesEntryGetResponse|ResponseInterface|null
     */
    public function setCurrentOrderInRegistry($orderId, $type)
    {
        $response = $this->orderHelper->getReturnDetailsAgainstId($orderId, $type);

        if ($response) {
            $this->setOrderInRegistry($response);
        }

        return $response;
    }

    /**
     * Set LS Central sales entry Object to registry
     * @param $order
     */
    public function setOrderInRegistry($order)
    {
        $this->registry->register('current_order', $order);
    }

    /**
     * Get respective magento order given Central sales entry Object
     *
     * @param $salesEntry
     */
    public function setCurrentMagOrderReturnInRegistry($salesEntry)
    {
        $order = $this->orderHelper->getOrderByDocumentId($salesEntry);
        $this->registry->register('current_mag_order', $order);
    }

    /**
     * Get hide shipping links flag
     * @return mixed
     */
    public function hideShippingLinks()
    {
        return $this->registry->registry('hide_shipping_links');
    }

    /**
     * Get respective magento order given Central sales entry Object
     *
     * @param $salesEntry
     */
    public function setCurrentMagOrderInRegistry($salesEntry)
    {
        $order = $this->orderHelper->getOrderByDocumentId($salesEntry);
        $this->registry->unregister('current_mag_order');
        $this->registry->register('current_mag_order', $order);
    }
}
