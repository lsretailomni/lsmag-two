<?php

namespace LS\Replication\Model\Message;

use \Ls\Core\Model\LSR;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;

/**
 * Class Invalid
 * @package LS\Replication\Model\Message
 */
class Invalid implements MessageInterface
{
    /**
     * @var UrlInterface
     */
    public $urlBuilder;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @param UrlInterface $urlBuilder
     * @param LSR $lsr
     */
    public function __construct(
        UrlInterface $urlBuilder,
        LSR $lsr
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->lsr        = $lsr;
    }

    /**
     * Check whether LS Retail configuration are valid or not
     *
     * @return bool
     */
    public function isDisplayed()
    {
        if ($this->lsr->isLSR()) {
            return false;
        }
        return true;
    }

    //@codeCoverageIgnoreStart

    /**
     * Retrieve unique message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        //@codingStandardsIgnoreStart
        return md5('LSR_INVALID');
        //@codingStandardsIgnoreEnd
    }

    /**
     * Retrieve message text
     *
     * @return Phrase
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl('admin/system_config/edit/section/ls_mag');
        //@codingStandardsIgnoreStart
        return __(
            '<strong>LS Retail Setup Incomplete</strong><br/>Please define the LS Retail Service Base URL and Web Store to proceed.<br/>Go to <a href="%1">Stores > Configuration > LS Retail > General Configuration</a>.',
            $url
        );
        //@codingStandardsIgnoreEnd
    }

    /**
     * Retrieve message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }

    //@codeCoverageIgnoreEnd
}
