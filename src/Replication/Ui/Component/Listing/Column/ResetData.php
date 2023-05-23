<?php

namespace Ls\Replication\Ui\Component\Listing\Column;

use \Ls\Replication\Block\Adminhtml\Grid\Renderer\Action\UrlBuilder;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ResetData
 * @package Ls\Replication\Ui\Component\Listing\Column
 */
class ResetData extends Column
{
    /** Url path */
    const URL_PATH_EXECUTE = 'ls_repl/deletion/lstables';

    /** @var UrlBuilder */
    public $actionUrlBuilder;

    /** @var UrlInterface */
    public $urlBuilder;

    /**
     * ResetData constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlBuilder $actionUrlBuilder
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlBuilder $actionUrlBuilder,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder       = $urlBuilder;
        $this->actionUrlBuilder = $actionUrlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $condition = __("Omni to Flat");
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['value'])) {
                    if ($item['condition'] == $condition) {
                        $item[$name]['reset'] = [
                            'href'    => $this->urlBuilder->getUrl(
                                self::URL_PATH_EXECUTE,
                                ['jobname' => $item['label'], 'store' => $item['storeId'], 'scope' => $item['scope']]
                            ),
                            'label'   => __('Reset'),
                            'confirm' => [
                                'title'   => __('Want to Reset Data for %1 Cron Job?', $item['label']),
                                'message' => __('It will take some time to reset data. Please don\'t close this window.
                                ')
                            ]
                        ];
                    }
                }
            }
        }
        return $dataSource;
    }
}
