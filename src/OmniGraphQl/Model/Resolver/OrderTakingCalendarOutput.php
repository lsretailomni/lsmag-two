<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver responsible to fetch order taking calendar
 */
class OrderTakingCalendarOutput implements ResolverInterface
{
    /**
     * @var DataHelper
     */
    public $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['store_id'])) {
            throw new GraphQlInputException(__('Required parameter "store_id" is missing'));
        }

        $storeId = $args['store_id'];

        $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsiteId();

        $slots = $this->dataHelper->getOrderTakingCalendarGivenStoreId($storeId, $websiteId);

        return ['dates' => $slots];
    }
}
