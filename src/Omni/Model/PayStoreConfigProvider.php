<?php
declare(strict_types=1);

namespace Ls\Omni\Model;

use \Ls\Omni\Model\Payment\PayStore;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;

class PayStoreConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    public $methodCode = PayStore::CODE;

    /**
     * @var MethodInterface
     */
    public $method;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     * @throws LocalizedException
     */
    public function __construct(
        public PaymentHelper $paymentHelper,
        public Escaper $escaper
    ) {
        $this->method  = $paymentHelper->getMethodInstance($this->methodCode);
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
                    'payableTo'      => $this->getPayableTo()
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
