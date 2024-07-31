<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Ui\DataProvider;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use \Ls\Replication\Ui\DataProvider\CronsProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class CronsProviderTest extends TestCase
{
    public const DATA_SOURCE_NAME = 'ls_repl_cron_grid_form_data_source';
    public const UI_COMPONENT_NAME = 'ls_repl_cron_grid_form';
    private $providerData = [
        'name' => self::DATA_SOURCE_NAME,
        'primaryFieldName' => 'id',
        'requestFieldName' => 'id',
    ];

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var UiComponentFactory */
    private $componentFactory;

    /** @var RequestInterface */
    private $request;

    /**
     * @var CronsProvider
     */
    private $cronsProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->request = $this->objectManager->get(RequestInterface::class);
        $this->componentFactory = $this->objectManager->get(UiComponentFactory::class);
        $this->cronsProvider = $this->objectManager->create(CronsProvider::class, $this->providerData);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
    ]
    public function testCronsGridForOmniToFlat(): void
    {
        $this->request->setParams(['scope' => ScopeInterface::SCOPE_WEBSITES, 'scope_id' => 1]);
        $items = $this->getComponentProvidedData(self::UI_COMPONENT_NAME);
        $this->assertGreaterThanOrEqual(1, $items);

        foreach ($items as $item) {
            $this->assertGivenKeys($item, [
                'id',
                'store',
                'scope_id',
                'fullreplicationstatus',
                'label',
                'lastexecuted',
                'value',
                'condition',
                'scope',
                'actions',
                'reset'
            ]);
            if (!empty($item['condition'])) {
                $this->assertNotEquals((string)  __('Flat to Magento'), $item['condition']);
            }
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
    ]
    public function testCronsGridForFlatToMagento(): void
    {
        $this->request->setParams(['scope' => ScopeInterface::SCOPE_STORES, 'scope_id' => 1]);
        $items = $this->getComponentProvidedData(self::UI_COMPONENT_NAME);
        $this->assertGreaterThanOrEqual(1, $items);

        foreach ($items as $item) {
            $this->assertGivenKeys($item, [
                'id',
                'store',
                'scope_id',
                'fullreplicationstatus',
                'label',
                'lastexecuted',
                'value',
                'condition',
                'scope',
                'actions',
                'reset'
            ]);
            if (!empty($item['condition'])) {
                if (!in_array($item['label'], $this->cronsProvider->getTranslationList()) ||
                    $item['label'] == 'repl_data_translation_to_magento'
                ) {
                    $this->assertEquals((string)  __('Flat to Magento'), $item['condition']);
                } else {
                    $this->assertEquals((string)  __('Omni to Flat'), $item['condition']);
                }
            }
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(
            StoreManager::XML_PATH_SINGLE_STORE_MODE_ENABLED,
            AbstractIntegrationTest::ENABLED,
            'store',
            'default'
        ),
    ]
    public function testCronsGridWithSingleStoreModeEnabled(): void
    {
        $this->request->setParams(['scope' => ScopeInterface::SCOPE_STORES, 'scope_id' => 1]);
        $items = $this->getComponentProvidedData(self::UI_COMPONENT_NAME);
        $this->assertGreaterThanOrEqual(1, $items);
        $condition1 = $condition2 = false;

        foreach ($items as $item) {
            $this->assertGivenKeys($item, [
                'id',
                'store',
                'scope_id',
                'fullreplicationstatus',
                'label',
                'lastexecuted',
                'value',
                'condition',
                'scope',
                'actions',
                'reset'
            ]);
            if (!empty($item['condition']) && !empty($item['label'])) {
                if ($item['label'] == AbstractIntegrationTest::SAMPLE_FLAT_REPLICATION_CRON_NAME &&
                    $item['condition'] == (string) __('Omni to Flat')
                ) {
                    $condition1 = true;
                }
                if ($item['label'] == AbstractIntegrationTest::SAMPLE_MAGENTO_REPLICATION_CRON_NAME &&
                    $item['condition'] == (string) __('Flat to Magento')
                ) {
                    $condition2 = true;
                }
            }
        }

        $this->assertTrue($condition1);
        $this->assertTrue($condition2);
    }

    public function assertGivenKeys($item, $keys)
    {
        foreach ($keys as $key) {
            if ($key == 'reset' && $item['condition'] != (string) __('Omni to Flat')) {
                continue;
            }
            $this->assertArrayHasKey($key, $item);
        }
    }

    /**
     * Call prepare method in the child components
     *
     * @param UiComponentInterface $component
     * @return void
     */
    private function prepareChildComponents(UiComponentInterface $component): void
    {
        foreach ($component->getChildComponents() as $child) {
            $this->prepareChildComponents($child);
        }

        $component->prepare();
    }

    /**
     * Get component provided data
     *
     * @param string $namespace
     * @return array
     * @throws LocalizedException
     */
    private function getComponentProvidedData(string $namespace): array
    {
        $component = $this->componentFactory->create($namespace);
        $this->prepareChildComponents($component);
        $dataSourceData = $component->getContext()->getDataSourceData($component);

        return $dataSourceData[self::DATA_SOURCE_NAME]['config']['data']['items'] ?? [];
    }
}
