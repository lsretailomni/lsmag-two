<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote\Address;
use Zend_Log_Exception;

class SelectedShippingMethodPlugin
{
    /**
     * After resolve plugin for selected shipping method
     *
     * @param $subject
     * @param $result
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve(
        $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        if ($result) {
            /** @var Address $address */
            $address = $value['model'];
            $cart = $address->getQuote();
            $result['cart']['model'] = $cart;
        }
        return $result;
    }
}
