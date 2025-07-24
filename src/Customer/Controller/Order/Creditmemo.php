<?php
declare(strict_types=1);

namespace Ls\Customer\Controller\Order;

use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;

/**
 * Controller being used for customer order returns
 */
class Creditmemo extends AbstractOrderController implements HttpGetActionInterface
{
    /**
     * @inheritDoc
     *
     * @return Page|ResultInterface|void
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->registerValuesInRegistry();

        if ($result) {
            return $result;
        }

        $this->orderHelper->registerGivenValueInRegistry('current_detail', 'creditmemo');
        $this->orderHelper->registerGivenValueInRegistry('hide_shipping_links', false);

        return $this->resultPageFactory->create();
    }

    /**
     * @inheritDoc
     *
     * @param $orderId
     * @param $type
     * @return mixed
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function fetchAndSetCurrentOrderInRegistry($orderId, $type)
    {
        parent::fetchAndSetCurrentOrderInRegistry($orderId, $type);

        $currentTransaction = current($this->getCurrentTransaction());
        $returnTransactions = $this->orderHelper->getReturnDetailsAgainstId(
            $currentTransaction->getDocumentId()
        );
        $newOrderId = null;

        if (!empty($returnTransactions)) {
            $lscMemberSalesBuffer = is_array($returnTransactions->getLscMemberSalesBuffer()) ?
                $returnTransactions->getLscMemberSalesBuffer() :
                [$returnTransactions->getLscMemberSalesBuffer()];

            foreach ($lscMemberSalesBuffer as $transaction) {
                $newOrderId[] = $transaction->getDocumentId();
            }
            $this->request->setParam('new_order_id', $newOrderId);
            $this->orderHelper->registerGivenValueInRegistry('current_order', $returnTransactions);
        }

        return $returnTransactions;
    }
}
