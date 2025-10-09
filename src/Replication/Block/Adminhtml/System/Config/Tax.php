<?php
declare(strict_types=1);

namespace Ls\Replication\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class for getting tax information
 */
class Tax implements OptionSourceInterface
{
    /**
     * @param ReplicationHelper $replicationHelper
     * @param LSR $lsr
     * @param RequestInterface $request
     */
    public function __construct(
        public ReplicationHelper $replicationHelper,
        public LSR $lsr,
        public RequestInterface $request
    ) {
    }

    /**
     * Getting data from tax setup
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function toOptionArray()
    {
        $taxCodes = [];
        $taxCodes[] = [
            'value' => 'DEFAULT' . '#' . "0.00",
            'label' => __('DEFAULT') . ' - ' . "0.00" . '%'
        ];
        $websiteId = (int)$this->request->getParam('website');
        $taxDataArray = $this->replicationHelper->getTaxSetup($websiteId);
        if (!empty($taxDataArray)) {
            foreach ($taxDataArray as $taxData) {
                $taxPercent = number_format((float)$taxData->getTaxPercent(), 2);
                $taxCodes[] = [
                    'value' => $taxData->getBusinessTaxGroup() . '#' . $taxData->getProductTaxGroup() .
                        '#' . $taxPercent,
                    'label' => __($taxData->getBusinessTaxGroup()) . ' - ' . __($taxData->getProductTaxGroup()) .
                        ' - ' . $taxPercent . '%'
                ];
            }

            return array_unique($taxCodes, SORT_REGULAR);
        }

        return $taxCodes;
    }
}
