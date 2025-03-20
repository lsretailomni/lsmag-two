<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Controller\Loyalty;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

class PointoffersTest extends AbstractController
{
    public $objectManager;
    public $contactHelper;
    public $fixtures;
    public $customerSession;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager   = Bootstrap::getObjectManager();
        $this->contactHelper   = $this->objectManager->get(ContactHelper::class);
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_LOYALTY_ENABLE_LOYALTY_ELEMENTS, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_LOYALTY_SHOW_POINT_OFFERS, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        )
    ]
    public function testExecuteWithRequiredConfigurations()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->dispatch('customer/loyalty/pointoffers');
        $content = $this->getResponse()->getBody();
        $this->assertStringContainsString('Point Offers', $content);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, 0, 'store', 'default'),
        Config(LSR::SC_LOYALTY_SHOW_POINT_OFFERS, 0, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        )
    ]
    public function testExecuteWithoutConfigurations()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->dispatch('customer/loyalty/pointoffers');
        $this->assertRedirect(
            $this->stringContains('customer/account')
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithoutCustomer()
    {
        $this->dispatch('customer/loyalty/pointoffers');
        $this->assertRedirect(
            $this->stringContains('customer/account/login')
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        )
    ]
    public function testExecuteWithLsrDown()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->dispatch('customer/loyalty/pointoffers');
        $this->assertRedirect(
            $this->stringContains('customer/account')
        );
    }
}
