<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class LayoutProcessorPlugin
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class LayoutProcessorPlugin
{
    /**
     * @var Data
     */
    public $data;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /** @var GiftCardHelper */
    public $giftCardHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * LayoutProcessorPlugin constructor.
     * @param Data $data
     * @param LoyaltyHelper $loyaltyHelper
     * @param GiftCardHelper $giftCardHelper
     * @param LSR $lsr
     */
    public function __construct(
        Data $data,
        LoyaltyHelper $loyaltyHelper,
        GiftCardHelper $giftCardHelper,
        LSR $lsr
    ) {
        $this->data = $data;
        $this->loyaltyHelper = $loyaltyHelper;
        $this->giftCardHelper = $giftCardHelper;
        $this->lsr = $lsr;
    }

    /**
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     * @throws NoSuchEntityException
     */
    public function afterProcess(
        LayoutProcessor $subject,
        array $jsLayout
    ) {
        if ($this->data->isCouponsEnabled('checkout') == '0') {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['discount']);
        }
        if ($this->loyaltyHelper->isLoyaltyPointsEnabled('checkout') == '0') {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['loyalty-points']);
            unset($jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children']['loyalty_points_label']);
        }
        if ($this->giftCardHelper->isGiftCardEnabled('checkout') == '0') {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['gift-card']);
        }

        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shippingAdditional']['children'])) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']
            ['shippingAdditional']['children']['select_store'] = ['component' => 'Ls_Omni/js/view/checkout/shipping/select-store'];
        } else {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shippingAdditional'] =
                [
                    'component' => "uiComponent",
                    'displayArea' => 'shippingAdditional',
                    'children' => [
                        'select_store' => [
                            'component' => 'Ls_Omni/js/view/checkout/shipping/select-store'
                        ]
                    ]
                ];
        }

        if (!$this->isValid()) {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shippingAdditional']['children']['select_store']);
        }
        return $jsLayout;
    }

    /**
     * @return bool|null
     * @throws NoSuchEntityException
     */
    public function isValid()
    {
        return $this->lsr->isLSR($this->lsr->getCurrentStoreId());
    }
}
