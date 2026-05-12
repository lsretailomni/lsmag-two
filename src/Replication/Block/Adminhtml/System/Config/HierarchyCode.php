<?php
declare(strict_types=1);

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
        // get current Website Id.
        $websiteId = (int)$this->request->getParam('website');
        $hierarchies = [];

        if ($this->lsr->validateBaseUrl('', [], [], $websiteId)) {
            $hierarchies = $this->helper->fetchWebStoreHierarchies();
        }

        if (!empty($hierarchies)) {
            $optionList = [['value' => '', 'label' => __('Please select your hierarchy code')]];
            foreach ($hierarchies as $hierarchy) {
                $optionList[] = [
                    'value' => $hierarchy->getHierarchyCode(),
                    'label' => $hierarchy->getDescription()
                ];
            }
        } else {
            $optionList = [['value' => '', 'label' => __('Store not set')]];
        }

        return $optionList;
    }
}
