<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\RegisterObserver;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\Manager;
use Magento\Framework\Registry;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;

class RegisterObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $customerSession;
    public $registry;
    public $controllerAction;
    public $registerObserver;
    public $contactHelper;
    public $messageManager;

    protected function setUp(): void
    {
        $this->objectManager           = Bootstrap::getObjectManager();
        $this->request                 = $this->objectManager->get(HttpRequest::class);
        $this->registerObserver        = $this->objectManager->get(RegisterObserver::class);
        $this->customerSession         = $this->objectManager->get(CustomerSession::class);
        $this->registry                = $this->objectManager->get(Registry::class);
        $this->controllerAction        = $this->objectManager->get(Action::class);
        $this->contactHelper           = $this->objectManager->get(ContactHelper::class);
        $this->messageManager          = $this->objectManager->get(Manager::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL, self::ENABLED, 'store', 'default')
    ]
    public function testExecuteWithValidParameters()
    {
        $this->customerSession->setData('customer_id', self::CUSTOMER_ID);
        $memberContact = $this->contactHelper->search(self::EMAIL);
        $this->contactHelper->setValue(
            [
              'lsr_username' => self::USERNAME,
              'lsr_id' => self::LSR_ID,
              'lsr_cardid' => self::LSR_CARD_ID,
              'email' => self::EMAIL,
              'password' => self::PASSWORD,
              'contact' => $memberContact
            ]
        );
        $this->registerObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL, self::ENABLED, 'store', 'default')
    ]
    public function testExecuteWithNoCustomerInSession()
    {
        $memberContact = $this->contactHelper->search(self::EMAIL);
        $this->contactHelper->setValue(
            [
                'lsr_username' => self::USERNAME,
                'lsr_id' => self::LSR_ID,
                'lsr_cardid' => self::LSR_CARD_ID,
                'email' => self::EMAIL,
                'password' => self::PASSWORD,
                'contact' => $memberContact
            ]
        );
        $this->registerObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
        $this->assertNull($this->customerSession->getData('customer_id'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL, self::ENABLED, 'store', 'default')
    ]
    public function testExecuteWithNoCustomerInSessionAndWrongPassword()
    {
        $memberContact = $this->contactHelper->search(self::EMAIL);
        $this->contactHelper->setValue(
            [
                'lsr_username' => self::USERNAME,
                'lsr_id' => self::LSR_ID,
                'lsr_cardid' => self::LSR_CARD_ID,
                'email' => self::EMAIL,
                'password' => self::PASSWORD. '123',
                'contact' => $memberContact
            ]
        );
        $this->registerObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNotNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
        $this->assertNull($this->customerSession->getData('customer_id'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL, self::ENABLED, 'store', 'default')
    ]
    public function testExecuteWithoutParameters()
    {
        $this->customerSession->setData('customer_id', self::CUSTOMER_ID);

        $this->registerObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
        $this->assertNotNull($this->customerSession->getData('customer_id'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     */
    public function testExecuteWithLsrDown()
    {
        $this->customerSession->setData('customer_id', self::CUSTOMER_ID);
        $this->contactHelper->setValue(
            [
                'lsr_username' => self::USERNAME,
                'lsr_id' => self::LSR_ID,
                'lsr_cardid' => self::LSR_CARD_ID,
                'email' => self::EMAIL,
                'password' => self::PASSWORD. '123'
            ]
        );
        $this->registerObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));
        $customer = $this->customerSession->getCustomer();
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->registry->registry(LSR::REGISTRY_LOYALTY_LOGINRESULT));
        $this->assertNotNull($customer->getData('lsr_password'));
        $this->assertNotNull($this->customerSession->getData('customer_id'));
    }
}
