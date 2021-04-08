<?php

namespace Ls\CustomerGraphQl\Model\Resolver;

use \Ls\CustomerGraphQl\Helper\DataHelper;
use Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning sales orders related to customers
 */
class SalesEntries implements ResolverInterface
{

    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(
        DataHelper $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * Get customer sales entries
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return Value|mixed|void
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $pageSize = null;
        $orderId  = null;
        $type     = DocumentIdType::ORDER;
        if (!empty($args)) {
            if (isset($args['filter'])) {
                if (isset($args['filter']['id'])) {
                    $orderId = $args['filter']['id'];
                }
                if (isset($args['filter']['type'])) {
                    $type = $args['filter']['type'];
                }
            }
            if (isset($args['pageSize'])) {
                $pageSize = $args['pageSize'];
            }
        }

        if (!empty($orderId)) {
            return $this->dataHelper->getSalesEntryByDocumentId($context, $orderId, $type);
        }

        return $this->dataHelper->getSalesEntries($context, $pageSize);
    }
}
