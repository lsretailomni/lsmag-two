<?php

namespace Ls\Omni\Block\GiftCardBalance;

use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

/**
 * To check gift card balance
 */
class GiftCardBalance extends Template
{
    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * @var Data
     */
    public $giftCardHelper;

    /**
     * @var Data
     */
    public $logger;

    /**
     * @var array
     */
    public $layoutProcessors;

    /**
     * GiftCardBalance constructor.
     * @param Template\Context $context
     * @param GiftCardHelper $giftCardHelper
     * @param LoggerInterface $logger
     * @param array $layoutProcessors
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        GiftCardHelper $giftCardHelper,
        LoggerInterface $logger,
        array $layoutProcessors = [],
        array $data = []
    ) {

        $this->giftCardHelper = $giftCardHelper;
        $this->logger         = $logger;
        parent::__construct($context, $data);
        $this->layoutProcessors = $layoutProcessors;
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
     * @return string
     * @throws NoSuchEntityException
     */
    public function isPinCodeFieldEnable()
    {
        return $this->giftCardHelper->isPinCodeFieldEnable();
    }
}
