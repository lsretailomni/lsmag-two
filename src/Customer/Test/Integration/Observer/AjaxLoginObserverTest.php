<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use Laminas\Http\Headers;
use Ls\Core\Model\LSR;
use Ls\Customer\Observer\AjaxLoginObserver;
use Ls\Customer\Test\Fixture\CustomerFixture;
use Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;

class AjaxLoginObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $ajaxLoginObserver;
    public $customerSession;
    public $registry;
    public $controllerAction;
    public $event;
    public $response;

    protected function setUp(): void
    {
        $this->objectManager     = Bootstrap::getObjectManager();
        $this->request           = $this->objectManager->get(HttpRequest::class);
        $this->ajaxLoginObserver = $this->objectManager->get(AjaxLoginObserver::class);
        $this->customerSession   = $this->objectManager->get(CustomerSession::class);
        $this->registry          = $this->objectManager->get(Registry::class);
        $this->controllerAction  = $this->objectManager->get(Action::class);
        $this->event             = $this->objectManager->get(Event::class);
        $this->response = $this->objectManager->get(ResponseInterface::class);
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
            'username'        => self::USERNAME,
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

        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }
}
