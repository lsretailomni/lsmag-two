<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
    private $providerData = [
        'name' => 'ls_repl_cron_grid_form_data_source',
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
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
    ]
    public function testCronsGridForOmniToFlat(): void
    {
        $this->request->setParams(['scope' => ScopeInterface::SCOPE_WEBSITES, 'scope_id' => 1]);
        $data = $this->getComponentProvidedData('ls_repl_cron_grid_form');
        $items = $data['items'];
        $this->assertGreaterThanOrEqual(1, $items);

        foreach ($items as $item) {
            if (!empty($item['condition'])) {
                $this->assertNotEquals((string)  __('Flat to Magento'), $item['condition']);
            }
        }
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
    ]
    public function testCronsGridForFlatToMagento(): void
    {
        $this->request->setParams(['scope' => ScopeInterface::SCOPE_STORES, 'scope_id' => 1]);
        $data = $this->getComponentProvidedData('ls_repl_cron_grid_form');
        $items = $data['items'];
        $this->assertGreaterThanOrEqual(1, $items);

        foreach ($items as $item) {
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

        return $component->getContext()->getDataProvider()->getData();
    }
}
