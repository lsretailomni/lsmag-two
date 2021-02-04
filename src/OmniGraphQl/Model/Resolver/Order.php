<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning document id coming from the Ls Central
 */
class Order implements ResolverInterface
{

    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Order constructor.
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return string
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (isset($value['order_number']) && $this->dataHelper->getOrderIdByIncrementId($value['order_number'])) {
            $order = $this->dataHelper->getOrderIdByIncrementId($value['order_number']);
        }
        return !empty($order) ? $order->getDocumentId() : 'N\A';
    }
}
