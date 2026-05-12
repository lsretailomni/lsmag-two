<?php
declare(strict_types=1);

namespace Ls\Omni\Block\GiftCardBalance;

use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Psr\Log\LoggerInterface;

class GiftCardBalance extends Template
{
    /**
     * @param Template\Context $context
     * @param GiftCardHelper $giftCardHelper
     * @param LoggerInterface $logger
     * @param array $layoutProcessors
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        public GiftCardHelper $giftCardHelper,
        public LoggerInterface $logger,
        public array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Getting layout
     *
     * @return string
     */
    public function getJsLayout()
    {
        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }
        return parent::getJsLayout();
    }

    /**
     * Check pin code field in enable or not in gift card
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPinCodeFieldEnable()
    {
        return $this->giftCardHelper->isPinCodeFieldEnable();
    }
}
