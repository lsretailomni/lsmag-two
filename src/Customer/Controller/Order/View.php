<?php

namespace Ls\Customer\Controller\Order;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntryGetResponse;
use \Ls\Omni\Client\Ecommerce\Entity\SalesEntry;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller being used for customer order detail
 */
class View extends Action
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
     * @var LSR
     */
    public $lsr;

    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Http $request
     * @param OrderHelper $orderHelper
     * @param Registry $registry
     * @param ResultFactory $result
     * @param ManagerInterface $messageManager
     * @param LSR $LSR
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Http $request,
        OrderHelper $orderHelper,
        Registry $registry,
        ResultFactory $result,
        ManagerInterface $messageManager,
        LSR $LSR
    ) {
        $this->resultRedirect    = $result;
        $this->messageManager    = $messageManager;
        $this->request           = $request;
        $this->registry          = $registry;
        $this->orderHelper       = $orderHelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->lsr               = $LSR;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidEnumException
     */
    public function execute()
    {
        $response = null;
        if ($this->request->getParam('order_id')) {
            $docId = $this->request->getParam('order_id');
            $type  = $this->request->getParam('type');
            if (empty($type)) {
                $type = DocumentIdType::ORDER;
            }
            /** @var SalesEntry|null $response */
            $response = $this->setCurrentOrderInRegistry($docId, $type);
            if ($response === null || !$this->orderHelper->isAuthorizedForOrder($response)) {
                return $this->_redirect('sales/order/history/');
            }
            $this->setCurrentMagOrderInRegistry($response);
            $this->registry->register('current_invoice_option', false);
        }
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

    /**
     * Set currentOrder into registry
     *
     * @param $docId
     * @param $type
     * @return SalesEntry|SalesEntryGetResponse|ResponseInterface|null
     * @throws InvalidEnumException
     */
    public function setCurrentOrderInRegistry($docId, $type)
    {
        $response = $this->orderHelper->getOrderDetailsAgainstId($docId, $type);
        if ($response) {
            $this->setOrderInRegistry($response);
        }
        return $response;
    }

    /**
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
    public function setCurrentMagOrderInRegistry($salesEntry)
    {
        $order = $this->orderHelper->getOrderByDocumentId($salesEntry);
        $this->registry->register('current_mag_order', $order);
    }
}
