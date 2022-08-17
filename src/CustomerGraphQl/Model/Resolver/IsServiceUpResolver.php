<?php
namespace Ls\CustomerGraphQl\Model\Resolver;

use \Ls\Core\Model\LSR;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning Enable/Disable status of config path mappings
 * based on system configuration and Omni online/offline mode
 */
class IsServiceUpResolver implements ResolverInterface
{
    /**
     * @var LSR
     */
    private LSR $lsr;

    private const CONFIG_PATHS_MAPPING = [
        'show_club_information'       => LSR::SC_LOYALTY_SHOW_CLUB_INFORMATION
    ];

    /**
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr
    ) {
        $this->lsr = $lsr;
    }

    /**
     * Fetch store configuration value based on omni online/offline status.
     *
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
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) &&
            isset(self::CONFIG_PATHS_MAPPING[$field->getName()])
        ) {
            return (bool)$this->lsr->getStoreConfig(
                self::CONFIG_PATHS_MAPPING[$field->getName()],
                $this->lsr->getCurrentStoreId()
            );
        }
        return false;
    }
}
