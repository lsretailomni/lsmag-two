<?php

namespace LS\Replication\Model\Message;

use \Ls\Core\Model\LSR;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * Invalid constructor.
     * @param UrlInterface $urlBuilder
     * @param LSR $lsr
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        UrlInterface $urlBuilder,
        LSR $lsr,
        StoreManagerInterface $storeManager
    ) {
        $this->urlBuilder   = $urlBuilder;
        $this->lsr          = $lsr;
        $this->storeManager = $storeManager;
    }

    /**
     * Check whether LS Retail configuration are valid or not
     *
     * @return bool
     */
    public function isDisplayed()
    {

        /**
         * The Idea is for Multi Store, if any of the store has isLSR setup?
         * then in that case we dont need to throw this error.
         */
        $displayNotice = true;

        /** @var StoreInterface[] $stores */
        $stores = $this->storeManager->getStores();
        if (!empty($stores)) {
            /** @var StoreInterface $store */
            foreach ($stores as $store) {
                if ($this->lsr->isLSR($store->getId())) {
                    $displayNotice = false;
                    break;
                }
            }
        }
        return $displayNotice;
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
