<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
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
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
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
        $expected = json_encode(['errors' => true, 'message' => 'Invalid Omni login or Omni password']);

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
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
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
        $expected = json_encode(['errors' => true, 'message' => 'Invalid Omni login or Omni password']);

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
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
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
        $expected = json_encode(['errors' => true, 'message' => 'Invalid Omni login or Omni password']);

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
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username'   => AbstractIntegrationTest::USERNAME,
                'lsr_id'     => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
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
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username'   => AbstractIntegrationTest::USERNAME,
                'lsr_id'     => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
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
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
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
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
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
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
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
                'lsr_token' => AbstractIntegrationTest::CUSTOMER_ID
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
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
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
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
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
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }
}
