<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Omni\Client\Ecommerce\Entity\Enum\StoreHourCalendarType;
use \Ls\OmniGraphQl\Helper\DataHelper;
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

        $slots['pickup_dates'] = $this->dataHelper->getOrderTakingCalendarGivenStoreId($storeId, $websiteId);
        $slots['delivery_dates'] = $this->dataHelper->getOrderTakingCalendarGivenStoreId(
            $storeId,
            $websiteId,
            StoreHourCalendarType::RECEIVING
        );

        return ['pickup_dates' => $slots['pickup_dates'], 'delivery_dates' => $slots['delivery_dates']];
    }
}
