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
        $taxCodes     = [];
        $taxCodes[]   = [
            'value' => 0.00,
            'label' => __('DEFAULT') . ' - ' . "0.00" . '%'
        ];
        $taxDataArray = $this->replicationHelper->getTaxSetup();
        if (!empty($taxDataArray)) {
            foreach ($taxDataArray as $taxData) {
                $taxPercent = number_format($taxData->getTaxPercent(), 2);
                $taxCodes[] = [
                    'value' => $taxPercent,
                    'label' => __($taxData->getBusinessTaxGroup()) . ' - ' . __($taxData->getProductTaxGroup()) .
                        ' - ' . $taxPercent . '%'
                ];
            }

            return array_unique($taxCodes, SORT_REGULAR);
        }

        return $taxCodes;
    }
}
