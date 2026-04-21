<?php
namespace Ls\Core\Model\Resolver;

use \Ls\Core\Model\LSR;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning Enable/Disable status
 */
class IsBasketIntegrationEnabledResolver implements ResolverInterface
{
    /**
     * @var LSR
     */
    public LSR $lsr;

    /**
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr
    ) {
        $this->lsr = $lsr;
    }

    /**
     * Fetch store configuration value based on integration status.
     *
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return bool
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return $this->lsr->getBasketIntegrationOnFrontend();
    }
}
