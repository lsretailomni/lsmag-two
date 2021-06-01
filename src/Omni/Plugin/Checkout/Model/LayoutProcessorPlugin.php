<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interceptor to intercept LayoutProcessor on checkout to remove required sections
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
     * @var ContactHelper
     */
    public $contactHelper;

    /**
     * @param Data $data
     * @param LoyaltyHelper $loyaltyHelper
     * @param GiftCardHelper $giftCardHelper
     * @param LSR $lsr
     * @param ContactHelper $contactHelper
     */
    public function __construct(
        Data $data,
        LoyaltyHelper $loyaltyHelper,
        GiftCardHelper $giftCardHelper,
        LSR $lsr,
        ContactHelper $contactHelper
    ) {
        $this->data           = $data;
        $this->loyaltyHelper  = $loyaltyHelper;
        $this->giftCardHelper = $giftCardHelper;
        $this->lsr            = $lsr;
        $this->contactHelper  = $contactHelper;
    }

    /**
     * After plugin to intercept LayoutProcessor process method
     *
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     * @throws NoSuchEntityException
     */
    public function afterProcess(
        LayoutProcessor $subject,
        array $jsLayout
    ) {
        $shippingStep = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step'];
        $billingStep = &$jsLayout['components']['checkout']['children']['steps']['children']['billing-step'];
        $sideBar = &$jsLayout['components']['checkout']['children']['sidebar'];
        $shippingAdditional = &$shippingStep['children']['shippingAddress']['children']['shippingAdditional'];

        if ($this->data->isCouponsEnabled('checkout') == '0') {
            unset($billingStep['children']['payment']['children']['afterMethods']['children']['discount']);
        }

        if ($this->loyaltyHelper->isLoyaltyPointsEnabled('checkout') == '0' ||
            $this->contactHelper->getCardIdFromCustomerSession()) {
            unset($billingStep['children']['payment']['children']['afterMethods']['children']['loyalty-points']);
            unset($sideBar['children']['summary']['children']['totals']['children']['loyalty_points_label']);
        }

        if ($this->giftCardHelper->isGiftCardEnabled('checkout') == '0') {
            unset($billingStep['children']['payment']['children']['afterMethods']['children']['gift-card']);
        }

        if (isset($shippingAdditional['children'])) {
            $shippingAdditional['children']['select_store'] =
                ['component' => 'Ls_Omni/js/view/checkout/shipping/select-store'];
        } else {
            $shippingAdditional =
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
            unset($shippingAdditional['children']['select_store']);
        }

        return $jsLayout;
    }

    /**
     * Check to see commerce services connection status
     *
     * @return bool|null
     * @throws NoSuchEntityException
     */
    public function isValid()
    {
        return $this->lsr->isLSR($this->lsr->getCurrentStoreId());
    }
}
