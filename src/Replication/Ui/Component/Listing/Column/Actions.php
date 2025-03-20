<?php

namespace Ls\Replication\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /** Url path */
    public const URL_PATH_EXECUTE = 'ls_repl/cron/grid';

    /** @var UrlInterface */
    public $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder       = $urlBuilder;
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
                $name    = $this->getData('name');
                if (isset($item['value'])) {
                    $item[$name]['execute'] = [
                        'href'    => $this->urlBuilder->getUrl(
                            self::URL_PATH_EXECUTE,
                            [
                                'joburl' => $item['value'],
                                'jobname' => $item['label'],
                                'scope_id' => $item['scope_id'],
                                'scope' => $item['scope']
                            ]
                        ),
                        'label'   => __('Execute'),
                        'confirm' => [
                            'title'   => __('Want to process %1 Cron?', $item['label']),
                            'message' => __('It will take some time to process. Please don\'t close this window.')
                        ],
                        'ariaLabel' => $item['label'] . '_' . 'execute_label'
                    ];
                }
            }
        }
        return $dataSource;
    }
}
