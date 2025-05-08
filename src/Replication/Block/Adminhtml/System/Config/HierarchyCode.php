<?php

namespace Ls\Replication\Block\Adminhtml\System\Config;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class HierarchyCode implements OptionSourceInterface
{
    /**
     * @param LSR $lsr
     * @param Data $helper
     * @param RequestInterface $request
     */
    public function __construct(
        public LSR $lsr,
        public Data $helper,
        public RequestInterface $request
    ) {
    }

    /**
     * Get options list
     *
     * @return array
     * @throws GuzzleException|NoSuchEntityException
     */
    public function toOptionArray()
    {
        $websiteId = (int)$this->request->getParam('website');
        $webStore = $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $websiteId);
        $hierarchies = $this->helper->fetchWebStoreHierarchies(
            '',
            [],
            [],
            [
                'storeNo' => $webStore,
                'batchSize' => 100,
                'fullRepl' => true,
                'lastKey' => '',
                'lastEntryNo' => 0
            ]
        );

        if (!empty($hierarchies)) {
            $optionList = [['value' => '', 'label' => __('Please select your hierarchy code')]];
            foreach ($hierarchies as $hierarchy) {
                $optionList[] = [
                    'value' => $hierarchy['Hierarchy Code'],
                    'label' => $hierarchy['Description']
                ];
            }
        } else {
            $optionList = [['value' => '', 'label' => __('Store not set')]];
        }

        return $optionList;
    }
}
