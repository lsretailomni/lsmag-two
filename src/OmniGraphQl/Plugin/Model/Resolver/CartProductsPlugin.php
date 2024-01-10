<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * For sending cart updates to omni
 */
class CartProductsPlugin
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
     * Sending cart updates to omni
     * @param $subject
     * @param $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterResolve(
        $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsiteId();
        $userId    = $context->getUserId();
        $this->dataHelper->setCustomerValuesInSession($userId, $websiteId);

        if (isset($result['cart']['model'])) {
            $quote                   = $result['cart']['model'];
            $result['cart']['model'] = $this->dataHelper->triggerEventForCartChange($quote);
        } elseif (isset($result['model'])) {
            $quote           = $result['model'];
            $result['model'] = $this->dataHelper->triggerEventForCartChange($quote);
        }

        return $result;
    }
}
