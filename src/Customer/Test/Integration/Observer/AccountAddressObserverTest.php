<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\AccountAddressObserver;
use \Ls\Customer\Test\Fixture\CustomerAddressFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\Manager;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

class AccountAddressObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $accountAddressObserver;
    public $fixtures;
    public $messageManager;

    protected function setUp(): void
    {
        $this->objectManager          = Bootstrap::getObjectManager();
        $this->request                = $this->objectManager->get(HttpRequest::class);
        $this->accountAddressObserver = $this->objectManager->get(AccountAddressObserver::class);
        $this->messageManager         = $this->objectManager->get(Manager::class);
        $this->fixtures               = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
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
        $address  = $this->fixtures->get('address');
        $this->accountAddressObserver->execute(new Observer(
            [
                'request'          => $this->request,
                'customer'         => $customer,
                'customer_address' => $address
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
    public function testExecuteWithInValidParameters()
    {
        $customer = $this->fixtures->get('customer');
        $address  = $this->fixtures->get('address');
        $append   = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);
        $address->setData('city', $append . $append);
        $this->accountAddressObserver->execute(new Observer(
            [
                'request'          => $this->request,
                'customer'         => $customer,
                'customer_address' => $address
            ]
        ));

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
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
    public function testExecuteWithoutLsrUsername()
    {
        $customer = $this->fixtures->get('customer');
        $address  = $this->fixtures->get('address');
        $append   = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);
        $address->setData('city', $append . $append);
        $this->accountAddressObserver->execute(new Observer(
            [
                'request'          => $this->request,
                'customer'         => $customer,
                'customer_address' => $address
            ]
        ));

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) == 0);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithLsrDown()
    {
        $this->accountAddressObserver->execute(new Observer(
            [
                'request' => $this->request
            ]
        ));

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) == 0);
    }
}
