<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Controller;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

class ForgotPasswordPostTest extends AbstractController
{
    private $objectManager;
    public $contactHelper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->contactHelper    = $this->objectManager->get(ContactHelper::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store customer/captcha/enable 0
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username'   => AbstractIntegrationTest::USERNAME,
                'lsr_id'     => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID
            ],
            'customer'
        )
    ]

    public function testForgotPasswordWithCustomerExists(): void
    {
        $this->getRequest()->setPostValue(['email' => AbstractIntegrationTest::EMAIL]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('customer/account/forgotPasswordPost');

        $customer = $this->contactHelper->getCustomerByEmail(AbstractIntegrationTest::EMAIL);
        $this->assertNotNull($customer->getData('lsr_resetcode'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store customer/captcha/enable 0
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default')
    ]

    public function testForgotPasswordWithNonExistentCustomerInMagento(): void
    {
        $this->getRequest()->setPostValue(['email' => AbstractIntegrationTest::EMAIL]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('customer/account/forgotPasswordPost');

        $customer = $this->contactHelper->getCustomerByEmail(AbstractIntegrationTest::EMAIL);
        $this->assertNotNull($customer->getData('lsr_resetcode'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store customer/captcha/enable 0
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default')
    ]

    public function testForgotPasswordWithNonExistentCustomerInBothCentralAndMagento(): void
    {
        $this->getRequest()->setPostValue(['email' => '123'. AbstractIntegrationTest::EMAIL]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('customer/account/forgotPasswordPost');

        $customer = $this->contactHelper->getCustomerByEmail(AbstractIntegrationTest::EMAIL);
        $this->assertNull($customer->getData('lsr_resetcode'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store customer/captcha/enable 0
     */
    public function testForgotPasswordWithLsrDown(): void
    {
        $this->getRequest()->setPostValue(['email' => '123'. AbstractIntegrationTest::EMAIL]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('customer/account/forgotPasswordPost');

        $customer = $this->contactHelper->getCustomerByEmail(AbstractIntegrationTest::EMAIL);
        $this->assertNull($customer->getData('lsr_resetcode'));
    }
}
