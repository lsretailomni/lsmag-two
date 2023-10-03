<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Omni\Model\Api\ReturnPolicyManagement;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * To return policy text in graphql
 */
class ReturnPolicyOutput implements ResolverInterface
{
    /**
     * @var ReturnPolicyManagement
     */
    public $returnPolicyManagement;

    /**
     * @param ReturnPolicyManagement $returnPolicyManagement
     */
    public function __construct(
        ReturnPolicyManagement $returnPolicyManagement
    ) {
        $this->returnPolicyManagement = $returnPolicyManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['parent_sku'])) {
            throw new GraphQlInputException(__('Required parameter "parent_sku" is missing'));
        }

        $childSku = $storeId = '';
        $parentSku = $args['parent_sku'];

        if (!empty($args['store_id'])) {
            $storeId = $args['store_id'];
        }

        if (!empty($args['child_sku'])) {
            $childSku = $args['child_sku'];
        }

        $response = $this->returnPolicyManagement->getReturnPolicy($parentSku, $childSku, $storeId);

        return [
            'text' => $response,
        ];
    }
}
