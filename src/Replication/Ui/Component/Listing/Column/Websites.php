<?php

namespace Ls\Replication\Ui\Component\Listing\Column;

use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ColorStatus
 * @package Ls\Replication\Ui\Component\Listing\Column
 */
class Websites extends Column
{
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /** @var LSR */
    public $lsr;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param LSR $lsr
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        LSR $lsr,
        array $components = [],
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->lsr = $lsr;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     * @throws LocalizedException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if ($this->getData('name') == "scope_id") {
                    $item[$this->getData('name')] =
                        !$this->lsr->isSSM() ?
                            $this->storeManager->getWebsite($item['scope_id'])->getName() :
                            $this->getAdminWebsite()->getName();
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get admin website
     *
     * @return WebsiteInterface|null
     */
    public function getAdminWebsite()
    {
        $adminWebsite = null;

        foreach ($this->storeManager->getWebsites(true, true) as $store) {
            if ($store->getCode() == 'admin') {
                $adminWebsite = $store;
                break;
            }
        }

        return $adminWebsite;
    }
}
