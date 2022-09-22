<?php

namespace Ls\Webhooks\Model\Notification;

use Ls\Webhooks\Helper\Data;
use Ls\Webhooks\Logger\Logger;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;

/**
 * Notification email
 */
class EmailNotification extends AbstractNotification
{
    /**
     * @var TransportBuilder
     */
    public $transportBuilder;

    /**
     * @var StateInterface
     */
    public $inlineTranslation;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        array $data = []
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        parent::__construct($helper, $logger, $data);
    }

    /**
     * @inheritDoc
     */
    public function notify()
    {
    }
}
