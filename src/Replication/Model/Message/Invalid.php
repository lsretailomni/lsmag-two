<?php

namespace LS\Replication\Model\Message;

class Invalid implements \Magento\Framework\Notification\MessageInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Ls\Core\Model\LSR
     */
    protected $lsr;

    /**
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Ls\Core\Model\LSR $lsr
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Ls\Core\Model\LSR $lsr
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->lsr = $lsr;
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
        return md5('LSR_INVALID');
    }

    /**
     * Retrieve message text
     *
     * @return \Magento\Framework\Phrase
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
