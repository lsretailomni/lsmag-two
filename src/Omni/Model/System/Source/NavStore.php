<?php

namespace Ls\Omni\Model\System\Source;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\StoreGetType;
use \Ls\Omni\Client\Ecommerce\Entity\Store;
use \Ls\Omni\Client\Ecommerce\Operation\StoresGet;
use \Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class NavStore
 * @package Ls\Omni\Model\System\Source
 */
class NavStore implements OptionSourceInterface
{
    /**
     * @var LSR
     */
    public $lsr;

    /** @var \Magento\Framework\App\RequestInterface */
    public $request;

    /**
     * NavStore constructor.
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->lsr     = $lsr;
        $this->request = $request;
    }

    public function toOptionArray()
    {
        $option_array = [['value' => '', 'label' => __('Please select your web store')]];
        $stores       = $this->getNavStores();
        if (!empty($stores)) {
            foreach ($stores as $nav_store) {
                $option_array[] = ['value' => $nav_store->getId(), 'label' => $nav_store->getDescription()];
            }
        }
        return $option_array;
    }

    /**
     * @return Store[]
     */
    public function getNavStores()
    {

        // get current Website Id.
        $websiteId = (int)$this->request->getParam('website');
        $baseUrl   = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_BASE_URL, $websiteId);
        $lsKey     = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_LS_KEY, $websiteId);

        if ($this->lsr->validateBaseUrl($baseUrl, $lsKey)) {
            // @codingStandardsIgnoreLine
            if (version_compare($this->lsr->getOmniVersion(), '2023.01', '>')) {
                $get_nav_stores = new StoresGet($baseUrl);
                $get_nav_stores->getOperationInput()->setStoreType(StoreGetType::WEB_STORE);
            } else {
                $get_nav_stores = new StoresGetAll($baseUrl);
            }
            $get_nav_stores->setToken($lsKey);
            $result = $get_nav_stores->execute();

            if ($result != null) {
                $result = $result->getResult();
            }
            if (!is_array($result)) {
                return $resultArray[] = $result;
            } else {
                return $result;
            }
        }
        return [];
    }
}
