<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Ui\Component;

use Magento\Framework\Api\Filter;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
abstract class AbstractDataProvider extends TestCase
{
    /** @var ObjectManagerInterface */
    public $objectManager;

    public $dataProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager    = Bootstrap::getObjectManager();
        $this->dataProvider = $this->objectManager->create(
            DataProvider::class,
            $this->getProviderData()
        );
    }

    public function assertDataExists()
    {
        $data = $this->dataProvider->getData();

        $this->assertGreaterThanOrEqual(1, $data['items']);
    }

    public function applyFilterToData($filterData)
    {
        $filter = $this->objectManager->create(
            Filter::class,
            ['data' => $filterData]
        );
        $this->dataProvider->addFilter($filter);
        return $this->dataProvider->getData();
    }

    /**
     * @return array
     */
    public function getDataByIdProvider(): array
    {
        return [
            [[
                 'condition_type' => 'eq',
                 'field' => $this->getSearchFieldName(),
                 'value' => $this->getSearchFieldValue()
             ]],
        ];
    }

    public function getProviderData()
    {
        return [
            'name'             => $this->getDataSourceName(),
            'primaryFieldName' => $this->getPrimaryFieldName(),
            'requestFieldName' => $this->getRequestFieldName(),
        ];
    }
    abstract public function getSearchFieldName();
    abstract public function getSearchFieldValue();
    abstract public function getDataSourceName();
    abstract public function getPrimaryFieldName();
    abstract public function getRequestFieldName();
}
