<?php
declare(strict_types=1);

namespace Ls\Omni\Model\System\Source;

use GuzzleHttp\Exception\GuzzleException;
use Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class NavStore implements OptionSourceInterface
{
    /**
     * @param Data $helper
     * @param LSR $lsr
     * @param RequestInterface $request
     */
    public function __construct(
        public Data $helper,
        public  \Ls\Core\Model\LSR $lsr,
        public \Magento\Framework\App\RequestInterface $request
    ) {
    }

    /**
     * Get options list
     *
     * @return array|array[]
     * @throws NoSuchEntityException
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
     * @throws NoSuchEntityException
     */
    public function getNavStores()
    {
        // get current Website Id.
        $websiteId = (int)$this->request->getParam('website');

        if ($this->lsr->validateBaseUrl('', [], [], $websiteId)) {
            return $this->helper->fetchWebStores();
        }

        return [];
    }
}
