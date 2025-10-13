<?php
declare(strict_types=1);

namespace Ls\CustomerGraphQl\Model\Resolver;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\CustomerGraphQl\Helper\DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For returning account information
 */
class Account implements ResolverInterface
{
    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(
        public DataHelper $dataHelper
    ) {
    }

    /**
     * For returning member contact information
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return $this->dataHelper->getMembersInfo($context);
    }
}
