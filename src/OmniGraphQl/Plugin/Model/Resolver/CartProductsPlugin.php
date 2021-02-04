<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteFactory;

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
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * CartProductsPlugin constructor.
     * @param DataHelper $dataHelper
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        DataHelper $dataHelper,
        QuoteFactory $quoteFactory
    ) {
        $this->dataHelper = $dataHelper;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function afterResolve(
        $subject,
        $result
    ) {
        if (isset($result['cart']) && isset($result['cart']['model'])) {
            $quote = $result['cart']['model'];
            $result['cart']['model'] = $this->dataHelper->triggerEventForCartChange($quote);
        } elseif (isset($result['model'])) {
            $quote = $result['model'];
            $result['model'] = $this->dataHelper->triggerEventForCartChange($quote);
        }
        return $result;
    }
}
