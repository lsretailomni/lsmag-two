<?php

namespace Ls\Replication\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store as StoreManager;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ColorStatus
 * @package Ls\Replication\Ui\Component\Listing\Column
 */
class Stores extends Column
{
    /**
     * @var StoreManager
     */
    public $storeManager;

    /**
     * Stores constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManager $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManager $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if ($this->getData('name') == "scope_id") {
                    $item[$this->getData('name')] = $this->storeManager->getStoreName($item['scope_id']);
                }
            }
        }

        return $dataSource;
    }
}
