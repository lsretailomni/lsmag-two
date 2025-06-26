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
     * @throws GuzzleException|NoSuchEntityException
     */
    public function toOptionArray()
    {
        $stores       = current($this->getNavStores());
        $optionList = [['value' => '', 'label' => __('Please select your web store')]];
        foreach ($stores->getLSCStore() ?? [] as $store) {
            $optionList[] = ['value' => $store['No.'], 'label' => $store['Name']];
        }
        return $optionList;
    }

    /**
     * Get nav stores
     *
     * @return array
     * @throws GuzzleException|NoSuchEntityException
     */
    public function getNavStores()
    {
        return $this->helper->fetchWebStores();
    }
}
