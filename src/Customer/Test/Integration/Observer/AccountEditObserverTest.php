<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\AccountEditObserver;
use \Ls\Customer\Test\Fixture\CustomerAddressFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\Manager;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

class AccountEditObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $accountEditObserver;
    public $fixtures;
    public $messageManager;
    public $controllerAction;
    public $customerSession;

    protected function setUp(): void
    {
        $this->objectManager       = Bootstrap::getObjectManager();
        $this->request             = $this->objectManager->get(HttpRequest::class);
        $this->accountEditObserver = $this->objectManager->get(AccountEditObserver::class);
        $this->messageManager      = $this->objectManager->get(Manager::class);
        $this->controllerAction    = $this->objectManager->get(Action::class);
        $this->customerSession     = $this->objectManager->get(CustomerSession::class);
        $this->fixtures            = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID,
                'lsr_account_id' => AbstractIntegrationTest::ACCOUNT_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        )

    ]
    public function testExecuteWithValidParameters()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->request->setParams(
            [
                'firstname'             => 'Test',
                'lastname'              => 'Test',
                'change_password'       => 1,
                'assistance_allowed'    => 1,
                'current_password'      => self::PASSWORD,
                'password'              => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
            ]
        );
        $this->accountEditObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
            ]
        ));

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
        $this->assertInstanceOf('Magento\Framework\Message\Success', current($messages));
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => null,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID,
                'lsr_account_id' => AbstractIntegrationTest::ACCOUNT_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        )

    ]
    public function testExecuteWithInValidParameters()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->request->setParams(
            [
                'firstname'             => 'Test',
                'lastname'              => 'Test',
                'change_password'       => 1,
                'assistance_allowed'    => 1,
                'current_password'      => self::PASSWORD,
                'password'              => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
            ]
        );
        $this->accountEditObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
            ]
        ));

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
        $this->assertInstanceOf('Magento\Framework\Message\Error', current($messages));
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID,
                'lsr_account_id' => AbstractIntegrationTest::ACCOUNT_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        )

    ]
    public function testExecuteWithoutParameters()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->request->setParams(
            []
        );
        $this->accountEditObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
            ]
        ));

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) == 0);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID,
                'lsr_account_id' => AbstractIntegrationTest::ACCOUNT_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        )

    ]
    public function testExecuteWithoutMatchingPasswordAndPasswordConfirmation()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->request->setParams(
            [
                'firstname'             => 'Test',
                'lastname'              => 'Test',
                'change_password'       => 1,
                'assistance_allowed'    => 1,
                'current_password'      => self::PASSWORD,
                'password'              => self::PASSWORD.'1',
                'password_confirmation' => self::PASSWORD,
            ]
        );
        $this->accountEditObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
            ]
        ));

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
        $this->assertInstanceOf('Magento\Framework\Message\Error', current($messages));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithLsrDown()
    {
        $this->request->setParams(
            []
        );
        $this->accountEditObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
            ]
        ));

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) == 0);
    }
}
