<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use Laminas\Http\Headers;
use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\AjaxLoginObserver;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\Config;

class AjaxLoginObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $ajaxLoginObserver;
    public $customerSession;
    public $registry;
    public $controllerAction;
    public $event;
    public $fixtures;

    protected function setUp(): void
    {
        $this->objectManager     = Bootstrap::getObjectManager();
        $this->request           = $this->objectManager->get(HttpRequest::class);
        $this->ajaxLoginObserver = $this->objectManager->get(AjaxLoginObserver::class);
        $this->customerSession   = $this->objectManager->get(CustomerSession::class);
        $this->registry          = $this->objectManager->get(Registry::class);
        $this->controllerAction  = $this->objectManager->get(Action::class);
        $this->event             = $this->objectManager->get(Event::class);
        $this->fixtures          = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
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
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
    ]
    public function testExecuteWithValidUsernameAndPassword()
    {
        $data = [
            'username'        => self::USERNAME,
            'password'        => self::PASSWORD,
            'captcha_form_id' => 'user_login',
            'context'         => 'checkout'
        ];

        $this->request
            ->setContent(json_encode($data))
            ->setHeaders(Headers::fromString('X_REQUESTED_WITH: XMLHttpRequest'))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);
        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));

        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
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
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
    ]
    public function testExecuteWithInValidEmailAndPassword()
    {
        $data = [
            'username'        => self::EMAIL,
            'password'        => self::PASSWORD. '123',
            'captcha_form_id' => 'user_login',
            'context'         => 'checkout'
        ];

        $this->request
            ->setContent(json_encode($data))
            ->setHeaders(Headers::fromString('X_REQUESTED_WITH: XMLHttpRequest'))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);
        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));
        $content = $this->controllerAction->getResponse()->getContent();
        $expected = json_encode(['errors' => true, 'message' => 'Invalid LS Central login or password.']);

        $this->assertEquals(
            $expected,
            $content
        );
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
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
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
    ]
    public function testExecuteWithInValidUsernameAndPassword()
    {
        $data = [
            'username'        => self::USERNAME. '123',
            'password'        => self::PASSWORD. '123',
            'captcha_form_id' => 'user_login',
            'context'         => 'checkout'
        ];

        $this->request
            ->setContent(json_encode($data))
            ->setHeaders(Headers::fromString('X_REQUESTED_WITH: XMLHttpRequest'))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);
        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));

        $content = $this->controllerAction->getResponse()->getContent();
        $expected = json_encode(['errors' => true, 'message' => 'Invalid LS Central login or password.']);

        $this->assertEquals(
            $expected,
            $content
        );
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
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
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
    ]
    public function testExecuteWithNonExistentEmailAndPasswordInBothCentralAndMagento()
    {
        $append      = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);

        $data = [
            'username'        => $append. $append. self::EMAIL,
            'password'        => self::PASSWORD. '123',
            'captcha_form_id' => 'user_login',
            'context'         => 'checkout'
        ];

        $this->request
            ->setContent(json_encode($data))
            ->setHeaders(Headers::fromString('X_REQUESTED_WITH: XMLHttpRequest'))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);
        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));

        $content = $this->controllerAction->getResponse()->getContent();
        $expected = json_encode(['errors' => true, 'message' => 'Invalid LS Central login or password.']);

        $this->assertEquals(
            $expected,
            $content
        );
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
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
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username'   => AbstractIntegrationTest::USERNAME,
                'lsr_id'     => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID,
                'lsr_account_id' => AbstractIntegrationTest::ACCOUNT_ID
            ],
            as: 'customer'
        ),
    ]
    public function testExecuteWithExistentEmailAndPasswordInBothCentralAndMagento()
    {
        $customer = $this->fixtures->get('customer');
        $data = [
            'username'        => self::EMAIL,
            'password'        => self::PASSWORD,
            'captcha_form_id' => 'user_login',
            'context'         => 'checkout'
        ];

        $this->request
            ->setContent(json_encode($data))
            ->setHeaders(Headers::fromString('X_REQUESTED_WITH: XMLHttpRequest'))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);
        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));

        $this->assertEquals($customer->getId(), $this->customerSession->getCustomerId());
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
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
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username'   => AbstractIntegrationTest::USERNAME,
                'lsr_id'     => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID,
                'lsr_account_id' => AbstractIntegrationTest::ACCOUNT_ID
            ],
            as: 'customer'
        ),
    ]
    public function testExecuteWithExistentUsernameAndPasswordInBothCentralAndMagento()
    {
        $customer = $this->fixtures->get('customer');
        $data = [
            'username'        => self::USERNAME,
            'password'        => self::PASSWORD,
            'captcha_form_id' => 'user_login',
            'context'         => 'checkout'
        ];

        $this->request
            ->setContent(json_encode($data))
            ->setHeaders(Headers::fromString('X_REQUESTED_WITH: XMLHttpRequest'))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);
        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));
        $this->assertEquals($customer->getId(), $this->customerSession->getCustomerId());
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username'   => AbstractIntegrationTest::USERNAME,
                'lsr_id'     => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID,
                'lsr_account_id' => AbstractIntegrationTest::ACCOUNT_ID
            ],
            as: 'customer'
        ),
    ]
    public function testExecuteWithUsernameAndLsrDown()
    {
        $data = [
            'username'        => self::USERNAME,
            'password'        => self::PASSWORD,
            'captcha_form_id' => 'user_login',
            'context'         => 'checkout'
        ];

        $this->request
            ->setContent(json_encode($data))
            ->setHeaders(Headers::fromString('X_REQUESTED_WITH: XMLHttpRequest'))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);

        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username'   => AbstractIntegrationTest::USERNAME,
                'lsr_id'     => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID,
                'lsr_account_id' => AbstractIntegrationTest::ACCOUNT_ID
            ],
            as: 'customer'
        ),
    ]
    public function testExecuteWithEmailAndLsrDown()
    {
        $data = [
            'username'        => self::EMAIL,
            'password'        => self::PASSWORD,
            'captcha_form_id' => 'user_login',
            'context'         => 'checkout'
        ];

        $this->request
            ->setContent(json_encode($data))
            ->setHeaders(Headers::fromString('X_REQUESTED_WITH: XMLHttpRequest'))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);

        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithoutLoginPayload()
    {
        $data = [];

        $this->request
            ->setContent(json_encode($data))
            ->setHeaders(Headers::fromString('X_REQUESTED_WITH: XMLHttpRequest'))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);

        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithoutAjaxHeader()
    {
        $data = [];

        $this->request
            ->setContent(json_encode($data))
            ->setMethod(Http::METHOD_POST);
        $this->event->setData([
            'request' => $this->request
        ]);

        $this->ajaxLoginObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }
}
