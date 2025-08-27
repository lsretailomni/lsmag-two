<?php
declare(strict_types=1);

namespace Ls\Omni\Model\System\Source;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\Data;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class NavStore implements OptionSourceInterface
{
    /**
     * @param Data $helper
     */
    public function __construct(
        public Data $helper
    ) {
    }

    /**
     * Get options list
     *
     * @return array|array[]
     */
    public function toOptionArray()
    {
        $stores = $this->getNavStores();
        $optionList = [['value' => '', 'label' => __('Please select your web store')]];

        foreach ($stores ?? [] as $store) {
            $optionList[] = ['value' => $store['No.'], 'label' => $store['Name']];
        }

        return $optionList;
    }

    /**
     * Get nav stores
     *
     * @return array|null
     */
    public function getNavStores()
    {
        return $this->helper->fetchWebStores();
    }
}
