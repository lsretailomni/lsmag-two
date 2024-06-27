<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use Laminas\Stdlib\Parameters;
use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\LoginObserver;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;

class LoginObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $loginObserver;
    public $customerSession;
    public $registry;
    public $controllerAction;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->request = $this->objectManager->get(HttpRequest::class);
        $this->loginObserver = $this->objectManager->get(LoginObserver::class);
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->controllerAction = $this->objectManager->get(Action::class);
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
        $this->request->setPost(
            new Parameters(['login' => ['username' => self::USERNAME, 'password' => self::PASSWORD]])
        );
        $this->loginObserver->execute(new Observer(
            [
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
        $this->request->setPost(
            new Parameters(['login' => ['username' => self::EMAIL, 'password' => self::PASSWORD. '123']])
        );
        $this->loginObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
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
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithInValidUsernameAndPassword()
    {
        $this->request->setPost(
            new Parameters(['login' => ['username' => self::USERNAME. '123' , 'password' => self::PASSWORD. '123']])
        );
        $this->loginObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
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
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithNonExistentEmailAndPasswordInBothCentralAndMagento()
    {
        $append      = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);

        $this->request->setPost(
            new Parameters(['login' => ['username' => $append. $append. self::EMAIL, 'password' => self::PASSWORD. '123']])
        );
        $this->loginObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithExistentEmailAndPasswordInBothCentralAndMagento()
    {
        $this->request->setPost(
            new Parameters(['login' => ['username' => self::EMAIL, 'password' => self::PASSWORD]])
        );
        $this->loginObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));
        $this->assertEquals($this->customerSession->getCustomerId(), self::CUSTOMER_ID);
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithExistentUsernameAndPasswordInBothCentralAndMagento()
    {
        $this->request->setPost(
            new Parameters(['login' => ['username' => self::USERNAME, 'password' => self::PASSWORD]])
        );
        $this->loginObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));
        $this->assertEquals($this->customerSession->getCustomerId(), self::CUSTOMER_ID);
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    public function testExecuteWithUsernameAndLsrDown()
    {
        $this->request->setPost(
            new Parameters(['login' => ['username' => self::USERNAME, 'password' => self::PASSWORD]])
        );
        $this->loginObserver->execute(new Observer(
            [
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
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    public function testExecuteWithEmailAndLsrDown()
    {
        $this->request->setPost(
            new Parameters(['login' => ['username' => self::EMAIL, 'password' => self::PASSWORD]])
        );
        $this->loginObserver->execute(new Observer(
            [
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
        $this->loginObserver->execute(new Observer(
            [
                'request' => $this->request
            ]
        ));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }
}
