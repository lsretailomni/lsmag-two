<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\SaveAfter;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;

class SaveAfterObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $controllerAction;
    public $saveAfterObserver;
    public $contactHelper;
    public $event;

    protected function setUp(): void
    {
        $this->objectManager      = Bootstrap::getObjectManager();
        $this->request            = $this->objectManager->get(HttpRequest::class);
        $this->saveAfterObserver = $this->objectManager->get(SaveAfter::class);
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
    public function testExecuteWithValidParametersAndCustomerExistsInCentral()
    {
        $customer = $this->contactHelper->getCustomerByEmail(self::EMAIL);
        $customer->addData(
            [
                'ls_password' => $this->contactHelper->encryptPassword(self::PASSWORD)
            ]
        );
        $this->event->setData([
            'customer' => $customer
        ]);

        $this->saveAfterObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));

        $this->assertNotNull($customer->getData('lsr_resetcode'));
        $this->assertNull($customer->getData('ls_password'));
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
    public function testExecuteWithValidParametersAndCustomerNonExistentInCentral()
    {
        $customer = $this->contactHelper->getCustomerByEmail(self::EMAIL);
        $append      = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);
        $customer->addData(
            [
                'ls_password' => $this->contactHelper->encryptPassword(self::PASSWORD),
                'email'     => $append. self::EMAIL,
                'lsr_username' => ''
            ]
        );
        $this->event->setData([
            'customer' => $customer
        ]);

        $this->saveAfterObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));

        $this->assertNotNull($customer->getData('lsr_resetcode'));
        $this->assertNull($customer->getData('ls_password'));
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
    public function testExecuteWithInValidParametersAndCustomerNonExistentInCentral()
    {
        $customer = $this->contactHelper->getCustomerByEmail(self::EMAIL);
        $append      = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);
        $customer->addData(
            [
                'ls_password' => $this->contactHelper->encryptPassword(self::PASSWORD),
                'email'     => $append. $append. self::EMAIL,
                'lsr_username' => ''
            ]
        );
        $this->event->setData([
            'customer' => $customer
        ]);

        $this->saveAfterObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));

        $this->assertNull($customer->getData('lsr_resetcode'));
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
    public function testExecuteWithoutLsPassword()
    {
        $customer = $this->contactHelper->getCustomerByEmail(self::EMAIL);
        $customer->addData(
            [
                'lsr_username' => ''
            ]
        );
        $this->event->setData([
            'customer' => $customer
        ]);

        $this->saveAfterObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));

        $this->assertNull($customer->getData('lsr_resetcode'));
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
                'ls_password' => $this->contactHelper->encryptPassword(self::PASSWORD)
            ]
        );
        $this->event->setData([
            'customer' => $customer
        ]);

        $this->saveAfterObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'event'             => $this->event
            ]
        ));

        $this->assertNull($customer->getData('lsr_resetcode'));
        $this->assertNull($customer->getData('ls_password'));
        $this->assertNotNull($customer->getData('lsr_password'));
    }
}
