<?php

namespace LS\Replication\Model\Message;

/**
 * Class Invalid
 * @package LS\Replication\Model\Message
 */
class Invalid implements \Magento\Framework\Notification\MessageInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $urlBuilder;

    /**
     * @var \Ls\Core\Model\LSR
     */
    public $lsr;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Ls\Core\Model\LSR $lsr
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Ls\Core\Model\LSR $lsr,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->lsr = $lsr;
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
         * The Idea is for Multi Store, if any of the store has isLSR setup? then in that case we dont need to thorw this error.
         */

        $displayNotice = true;

        /** @var \Magento\Store\Api\Data\StoreInterface[] $stores */
        $stores = $this->storeManager->getStores();
        if (!empty($stores)) {
            /** @var \Magento\Store\Api\Data\StoreInterface $store */
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
