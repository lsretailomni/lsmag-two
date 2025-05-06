<?php
namespace Ls\OmniGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning Enable/Disable status of config path mappings
 * based on system configuration and Omni online/offline mode
 */
class IsBasketIntegrationEnabledResolver extends IsServiceUpResolver
{
    /**
     * Fetch store configuration value based on omni online/offline status.
     *
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return bool
     * @throws NoSuchEntityException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $isServiceEnabled = parent::resolve($field, $context, $info, $value);

        return $isServiceEnabled && $this->lsr->getBasketIntegrationOnFrontend();
    }
}
