<?php

namespace Ls\Customer\Controller\Sales\Order;

use Exception;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Core\Model\LSR;
use Magento\Framework\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;
use Psr\Log\LoggerInterface;

/**
 * Class View
 * @package Ls\Customer\Controller\Sales\Order
 */
class View extends \Magento\Sales\Controller\Order\View
{

    /**@var Http $request */
    public $request;

    /** @var OrderRepositoryInterface */
    public $orderRepository;

    /** @var LoggerInterface */
    public $logger;

    /** @var LSR */
    public $lsr;

    /**
     * View constructor.
     * @param Http $request
     * @param Action\Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param LSR $lsr
     */
    public function __construct(
        Http $request,
        Action\Context $context,
        OrderLoaderInterface $orderLoader,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        LSR $lsr
    ) {
        parent::__construct($context, $orderLoader, $resultPageFactory);
        $this->request         = $request;
        $this->logger          = $logger;
        $this->orderRepository = $orderRepository;
        $this->lsr             = $lsr;
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            if ($this->request->getParam('order_id')) {
                $orderId    = $this->request->getParam('order_id');
                $order      = $this->getOrder($orderId);
                $documentId = $order->getDocumentId();
            }
            if (empty($documentId)) {
                return parent::execute();
            }
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            if (version_compare($this->lsr->getOmniVersion(), '4.5.0', '<')) {
                $resultRedirect->setPath(
                    'customer/order/view/order_id/' . $documentId . '/type/' . DocumentIdType::ORDER
                );
            } else {
                $resultRedirect->setPath('customer/order/view/order_id/' . $documentId);
            }
            return $resultRedirect;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return parent::execute();
        }
    }

    /**
     * @param $id
     * @return OrderInterface
     */
    public function getOrder($id)
    {
        return $this->orderRepository->get($id);
    }
}
