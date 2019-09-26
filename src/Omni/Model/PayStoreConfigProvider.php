<?php

namespace Ls\Omni\Model;

use \Ls\Omni\Model\Payment\PayStore;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Class PayStoreConfigProvider
 * @package Ls\Omni\Model
 */
class PayStoreConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    public $methodCode = PayStore::CODE;

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    public $method;

    /**
     * @var Escaper
     */
    public $escaper;

    /**
     * PayStoreConfigProvider constructor.
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'paystore' => [
                    'mailingAddress' => $this->getMailingAddress(),
                    'payableTo' => $this->getPayableTo()
                ],
            ],
        ] : [];
    }

    /**
     * Get mailing address from config
     *
     * @return string
     */
    public function getMailingAddress()
    {
        return nl2br($this->escaper->escapeHtml($this->method->getMailingAddress()));
    }

    /**
     * Get payable to from config
     *
     * @return string
     */
    public function getPayableTo()
    {
        return $this->method->getPayableTo();
    }
}
