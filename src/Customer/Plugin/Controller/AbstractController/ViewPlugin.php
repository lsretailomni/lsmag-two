<?php
declare(strict_types=1);

namespace Ls\Customer\Plugin\Controller\AbstractController;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Psr\Log\LoggerInterface;

class ViewPlugin
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var RedirectFactory
     */
    public $resultRedirectFactory;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var OrderHelper
     */
    public $orderHelper;

    /**
     * @param LSR $lsr
     * @param LoggerInterface $logger
     * @param RedirectFactory $resultRedirectFactory
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        LSR $lsr,
        LoggerInterface $logger,
        RedirectFactory $resultRedirectFactory,
        OrderHelper $orderHelper
    ) {
        $this->lsr                   = $lsr;
        $this->logger                = $logger;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderHelper           = $orderHelper;
    }

    /**
     * Around plugin to redirect order in case if needed
     *
     * @param $subject
     * @param $proceed
     * @return Redirect|mixed
     * @throws GuzzleException
     */
    public function aroundExecute($subject, $proceed)
    {
        $documentId = '';
        try {
            if ($subject->getRequest()->getParam('order_id')) {
                $orderId    = $subject->getRequest()->getParam('order_id');
                $order      = $this->orderHelper->getMagentoOrderGivenEntityId($orderId);
                $documentId = $order->getDocumentId();
            }
            if (empty($documentId) || !$this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                return $proceed();
            }
            $resultRedirect = $this->resultRedirectFactory->create();
            $actionName = $subject->getRequest()->getActionName();

            $resultRedirect->setPath(
                'customer/order/'. $actionName. '/order_id/' . $documentId . '/type/' . DocumentIdType::ORDER
            );
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $proceed();
        }
    }
}
