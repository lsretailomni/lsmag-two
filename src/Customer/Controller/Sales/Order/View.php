<?php
namespace Ls\Customer\Controller\Sales\Order;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\AbstractController\OrderLoaderInterface;

class View extends \Magento\Sales\Controller\Order\View
{

    /**
     * @var Http $request
     */
    public $request;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    public $orderRepository;

    /**
     * View constructor.
     * @param Http $request
     * @param Action\Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param PageFactory $resultPageFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Http $request,
        Action\Context $context,
        OrderLoaderInterface $orderLoader,
        PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context, $orderLoader, $resultPageFactory);
        $this->request = $request;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        if ($this->request->getParam('order_id')) {
            $orderId = $this->request->getParam('order_id');
            $order = $this->getOrder($orderId);
        } else {
            parent::execute();
        }
    }

    /**
     * @param $id
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder($id)
    {
        return $this->orderRepository->get($id);
    }
}
