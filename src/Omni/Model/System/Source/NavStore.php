<?php

namespace Ls\Omni\Model\System\Source;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Store;
use \Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class NavStore
 * @package Ls\Omni\Model\System\Source
 */
class NavStore implements ArrayInterface
{
    /**
     * @return array
     */
    /**
     * @var Ls\Core\Model\LSR
     */
    public $lsr;

    /** @var \Magento\Framework\App\RequestInterface  */
    public $request;

    /**
     * NavStore constructor.
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr,
        \Magento\Framework\App\RequestInterface $request)
    {
        $this->lsr = $lsr;
        $this->request = $request;
    }

    public function toOptionArray()
    {
        $option_array = [['value' => '', 'label' => __('Please select your web store')]];
        if (!empty($this->getNavStores())) {
            foreach ($this->getNavStores() as $nav_store) {
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
        $websiteId = (int) $this->request->getParam('website');
        $baseUrl = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_BASE_URL,$websiteId);

        if ($this->lsr->validateBaseUrl($baseUrl)) {
                // @codingStandardsIgnoreLine
                $get_nav_stores = new StoresGetAll($baseUrl);
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
