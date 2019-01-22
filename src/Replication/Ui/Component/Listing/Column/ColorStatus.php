<?php

namespace Ls\Replication\Ui\Component\Listing\Column;

/**
 * Class ColorStatus
 * @package Ls\Replication\Ui\Component\Listing\Column
 */
class ColorStatus extends \Magento\Ui\Component\Listing\Columns\Column
{

    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
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
                if ($this->getData('name') == "processed") {
                    if ($item['processed'] == "1") {
                        $item[$this->getData('name')] = '<div class="flag-green custom-grid-flag">Processed</div>';
                    } else {
                        $item[$this->getData('name')] = '<div class="flag-yellow custom-grid-flag">Not Processed</div>';
                    }
                } elseif ($this->getData('name') == "is_updated") {
                    if ($item['is_updated'] == "0") {
                        $item[$this->getData('name')] = '<div class="flag-green custom-grid-flag">Updated</div>';
                    } else {
                        $item[$this->getData('name')] = '<div class="flag-yellow custom-grid-flag">Not Updated</div>';
                    }
                }
            }
        }

        return $dataSource;
    }
}
