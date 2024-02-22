<?php

namespace Ls\Replication\Model\Message;

use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class LicenseInvalid implements MessageInterface
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
     * @throws NoSuchEntityException
     */
    public function isDisplayed()
    {
        $displayNotice = false;

        $stores = $this->storeManager->getStores();

        if (!empty($stores)) {
            /** @var StoreInterface $store */
            foreach ($stores as $store) {
                $str = $this->lsr->getCentralVersion($store->getWebsiteId(), ScopeInterface::SCOPE_WEBSITES);
                $centralVersion =  strstr($str, " ", true);

                if (version_compare($centralVersion, '24.0.0.0', '>=') &&
                    $this->lsr->getWebsiteConfig(
                        LSR::SC_SERVICE_LICENSE_VALIDITY,
                        $store->getWebsiteId()
                    ) === '0') {
                    $displayNotice = true;
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
        return md5('LSR_LICENSE_INVALID');
        //@codingStandardsIgnoreEnd
    }

    /**
     * Retrieve message text
     *
     * @return Phrase
     */
    public function getText()
    {
        $url = $this->urlBuilder->getUrl('adminhtml/system_config/edit/section/ls_mag');
        //@codingStandardsIgnoreStart
        return __(
            'You have an invalid or expired license for one of the Central instance configured currently.',
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
