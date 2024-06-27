<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\SaveBefore;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;

class SaveBeforeObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $controllerAction;
    public $saveBeforeObserver;
    public $contactHelper;
    public $event;

    protected function setUp(): void
    {
        $this->objectManager      = Bootstrap::getObjectManager();
        $this->request            = $this->objectManager->get(HttpRequest::class);
        $this->saveBeforeObserver = $this->objectManager->get(SaveBefore::class);
        $this->controllerAction   = $this->objectManager->get(Action::class);
        $this->contactHelper      = $this->objectManager->get(ContactHelper::class);
        $this->event              = $this->objectManager->get(Event::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithValidParameters()
    {
        $customer = $this->contactHelper->getCustomerByEmail(self::EMAIL);
        $customer->addData(
            [
                'lsr_username' => null,
                'lsr_id' => null,
                'lsr_cardid' => null,
                'password_hash' => null
            ]
        );
        $this->event->setData([
            'data_object' => $customer
        ]);

        $this->saveBeforeObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));

        $this->assertFalse($customer->getData('ls_validation'));
        $this->assertNotNull($customer->getData('ls_password'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithLsPassword()
    {
        $append      = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);

        $customer = $this->contactHelper->getCustomerByEmail(self::EMAIL);
        $customer->addData(
            [
                'ls_password' => self::PASSWORD,
                'email' => $append. self::EMAIL
            ]
        );
        $this->event->setData([
            'data_object' => $customer
        ]);

        $this->saveBeforeObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));

        $this->assertTrue($customer->getData('ls_validation'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithLsPasswordAndCustomerExists()
    {
        $customer = $this->contactHelper->getCustomerByEmail(self::EMAIL);
        $customer->addData(
            [
                'ls_password' => self::PASSWORD
            ]
        );
        $this->event->setData([
            'data_object' => $customer
        ]);
        $this->expectException(AlreadyExistsException::class);
        $this->saveBeforeObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithInvalidParameters()
    {
        $customer = $this->contactHelper->getCustomerByEmail(self::EMAIL);
        $customer->addData(
            [
                'lsr_username' => null,
                'lsr_id' => null,
                'lsr_cardid' => null,
                'password_hash' => null,
                'email' => null,
                'ls_password' => '124'
            ]
        );
        $this->event->setData([
            'data_object' => $customer
        ]);
        $this->expectException(InputException::class);
        $this->saveBeforeObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    public function testExecuteWithLsrDown()
    {
        $customer = $this->contactHelper->getCustomerByEmail(self::EMAIL);
        $customer->addData(
            [
                'ls_password' => self::PASSWORD
            ]
        );
        $this->event->setData([
            'data_object' => $customer
        ]);
        $this->saveBeforeObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));

        $this->assertTrue($customer->getData('ls_validation'));
    }
}
