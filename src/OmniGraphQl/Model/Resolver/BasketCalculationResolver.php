<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Omni\Block\Adminhtml\System\Config\BasketCalculation;
use \Ls\Core\Model\LSR;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning document id coming from the Ls Central
 */
class BasketCalculationResolver implements ResolverInterface
{
    /**
     * @var LSR
     */
    private LSR $lsr;
    private BasketCalculation $basketCalculation;

    /**
     * @param LSR $lsr
     * @param BasketCalculation $basketCalculation
     */
    public function __construct(
        LSR $lsr,
        BasketCalculation $basketCalculation
    ) {
        $this->lsr               = $lsr;
        $this->basketCalculation = $basketCalculation;
    }

    /**
     * Show club information
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $bcOptionLabel = null;
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $basketCalculateOption = $this->lsr->getStoreConfig(
                LSR::LS_PLACE_TO_SYNC_BASKET_CALCULATION,
                $this->lsr->getCurrentStoreId()
            );
            $bcOptionsArray = $this->basketCalculation->toOptionArray();
            foreach ($bcOptionsArray as $bcOption) {
                if ($bcOption['value'] == $basketCalculateOption) {
                    $bcOptionLabel = $bcOption['label'];
                    break;
                }
            }
            return $bcOptionLabel;
        }
        return false;
    }
}
