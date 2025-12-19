<?php

namespace Ls\Omni\Plugin\Email\Sender;

use Magento\Sales\Model\Order\Creditmemo;

/**
 * Credit memo email modification
 */
class CreditmemoSender
{
    /**
     * @param $subject
     * @param $proceed
     * @param Creditmemo $creditMemo
     * @param $forceSyncMode
     * @return mixed
     */
    public function aroundSend($subject, $proceed, Creditmemo $creditMemo, $forceSyncMode = false)
    {
        $incrementId = $creditMemo->getOrder()->getIncrementId();
        if (!empty($creditMemo->getOrder()->getDocumentId())) {
            $creditMemo->getOrder()->setIncrementId($creditMemo->getOrder()->getDocumentId());
        }
        $result = $proceed($creditMemo, $forceSyncMode);
        $creditMemo->getOrder()->setIncrementId($incrementId);
        return $result;
    }
}
