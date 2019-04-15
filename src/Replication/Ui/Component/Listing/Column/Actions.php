<?php

namespace Ls\Replication\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use \Ls\Replication\Block\Adminhtml\Grid\Renderer\Action\UrlBuilder;
use Magento\Framework\UrlInterface;

/**
 * Class Actions
 * @package Ls\Replication\Ui\Component\Listing\Column
 */
class Actions extends Column
{
    /** Url path */
    const URL_PATH_EXECUTE = 'ls_repl/cron/grid';

    /** @var UrlBuilder */
    public $actionUrlBuilder;

    /** @var UrlInterface */
    public $urlBuilder;

    /**
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
        $this->urlBuilder = $urlBuilder;
        $this->actionUrlBuilder = $actionUrlBuilder;
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
                $name = $this->getData('name');
                if (isset($item['value'])) {
                    $item[$name]['execute'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::URL_PATH_EXECUTE,
                            ['joburl' => $item['value'], 'jobname' => $item['label']]
                        ),
                        'label' => __('Execute'),
                        'confirm' => [
                            'title' => __('Want to process ${ $.$data.label } Cron?'),
                            'message' => __('It will take some time to process. Please don\'t close this window.')
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}
