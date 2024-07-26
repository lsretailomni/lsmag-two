<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Api\DiscountManagementInterface;
use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Interceptor to intercept PlaceOrder resolver methods
 */
class PlaceOrderPlugin
{
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @var DiscountManagementInterface
     */
    private $discountManagement;

    /**
     * @param DataHelper $dataHelper
     * @param LSR $lsr
     * @param DiscountManagementInterface $discountManagement
     */
    public function __construct(
        DataHelper $dataHelper,
        LSR $lsr,
        DiscountManagementInterface $discountManagement
    ) {
        $this->dataHelper = $dataHelper;
        $this->lsr = $lsr;
        $this->discountManagement = $discountManagement;
    }

    /**
     * Around method to intercept place order mutation
     *
     * @param $subject
     * @param $proceed
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlInputException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function aroundResolve(
        $subject,
        $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        if ($this->lsr->isGraphqlDiscountValidationEnabled()) {
            $response = $this->discountManagement->checkDiscountValidity($args['input']['cart_id']);
            $msg = [];

            foreach ($response as $each) {
                if ($each['valid'] === false) {
                    $msg[] = $each['msg']->getText();
                }
            }

            if (!empty($msg)) {
                throw new GraphQlInputException(
                    __(implode('', $msg))
                );
            }
        }

        $result = $proceed($field, $context, $info, $value, $args);

        if (isset($result['order']['order_number'])) {
            $order = $this->dataHelper->getOrderByIncrementId($result['order']['order_number']);

            if ($this->lsr->isPushNotificationsEnabled() && isset($args['input']['subscription_id'])) {
                $order->setLsSubscriptionId($args['input']['subscription_id']);
                $this->dataHelper->saveOrder($order);
            }
            $result['order']['document_id'] = !empty($order) && $order->getDocumentId() ? $order->getDocumentId() : '';
            $result['order']['pickup_store_id'] = !empty($order) && $order->getPickupStore() ?
                $order->getPickupStore() : '';
            $result['order']['pickup_store_name'] = !empty($order) && $order->getPickupStore() ?
                $this->dataHelper->getStoreNameById($order->getPickupStore()) : '';
        }

        return $result;
    }
}
