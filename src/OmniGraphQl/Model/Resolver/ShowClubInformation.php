<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Core\Model\LSR;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning document id coming from the Ls Central
 */
class ShowClubInformation implements ResolverInterface
{
    /**
     * @var LSR
     */
    private LSR $lsr;

    /**
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr
    ) {
        $this->lsr        = $lsr;
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
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            return (bool)$this->lsr->getStoreConfig(
                LSR::SC_LOYALTY_SHOW_CLUB_INFORMATION,
                $this->lsr->getCurrentStoreId()
            );
        }
        return false;
    }
}
