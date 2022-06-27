<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning all click and collect supported stores
 */
class ClickAndCollectStoresOutput implements ResolverInterface
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
        $salesType = '';

        if (!empty($args['hospitality_sales_type'])) {
            $salesType = $args['hospitality_sales_type'];
        }

        $scopeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $stores  = $this->dataHelper->getStores($scopeId, $salesType);
        $stores  = $stores->toArray()['items'];

        foreach ($stores as &$store) {
            $store = $this->dataHelper->formatStoreData($store);
        }

        return ['stores' => $stores];
    }
}
