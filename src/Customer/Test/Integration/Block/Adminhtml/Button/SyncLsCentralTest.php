<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Adminhtml\Button;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Block\Adminhtml\Button\SyncLsCentral;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class SyncLsCentralTest extends TestCase
{
    private $objectManager;
    private $button;
    private $registry;
    public $fixtures;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->button   = $this->objectManager->get(SyncLsCentral::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->fixtures = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetButtonDataWithoutCustomer(): void
    {
        $this->assertEmpty($this->button->getButtonData());
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => null,
                'lsr_id'       => null,
                'lsr_cardid'   => null
            ],
            'customer'
        )
    ]
    public function testGetButtonDataWithCustomer(): void
    {
        $customer = $this->fixtures->get('customer');
        $this->registry->unregister(RegistryConstants::CURRENT_CUSTOMER_ID);
        $this->registry->register(RegistryConstants::CURRENT_CUSTOMER_ID, $customer->getId());
        $data = $this->button->getButtonData();
        $this->assertNotEmpty($data);
        $this->assertEquals(__('Save to LS Central'), $data['label']);
        $this->assertStringContainsString(
            'lscustomer/account/sync/customer_id/' . $customer->getId() . '/',
            $data['on_click']
        );
    }
}
