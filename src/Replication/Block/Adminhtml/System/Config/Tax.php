<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplTaxSetup;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class for getting tax information
 */
class Tax implements OptionSourceInterface
{
    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /** @var RequestInterface */
    public $request;

    /**
     * ShippingTax constructor.
     * @param ReplicationHelper $replicationHelper
     * @param LSR $lsr
     * @param RequestInterface $request
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        LSR $lsr,
        RequestInterface $request
    ) {
        $this->replicationHelper = $replicationHelper;
        $this->lsr               = $lsr;
        $this->request           = $request;
    }

    /**
     * Getting data from tax setup
     * @return array
     * @throws NoSuchEntityException
     */
    public function toOptionArray()
    {
        // Get current Website Id.
        $websiteId  = (int)$this->request->getParam('website');
        $taxCodes   = [];
        $taxCodes[] = [
            'value' => 0.00,
            'label' => __('DEFAULT') . ' - ' . "0.00" . '%'
        ];
        if ($this->lsr->isLSR($websiteId, 'website')) {
            $taxData = $this->replicationHelper->getTaxSetup($websiteId);
            if ($taxData) {
                $data = $taxData->getTaxSetups()->getReplTaxSetup();
                foreach ($data as $item) {
                    if ($item instanceof ReplTaxSetup && $item->getIsDeleted() == false && $item->getTaxPercent() > 0 &&
                        !empty($item->getProductTaxGroup()) && !empty($item->getBusinessTaxGroup())) {
                        $taxPercent = number_format($item->getTaxPercent(), 2);
                        $taxCodes[] = [
                            'value' => $taxPercent,
                            'label' => __($item->getBusinessTaxGroup()) . ' - ' . __($item->getProductTaxGroup()) .
                                ' - ' . $taxPercent . '%'
                        ];
                    }
                }
            }
        }
        return $taxCodes;
    }
}
