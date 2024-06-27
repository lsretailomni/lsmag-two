<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Captcha\Observer\CheckUserCreateObserver;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\Manager;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;

class CheckUserCreateObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $customerSession;
    public $controllerAction;
    public $checkUserCreateObserver;
    public $contactHelper;
    public $messageManager;

    protected function setUp(): void
    {
        $this->objectManager           = Bootstrap::getObjectManager();
        $this->request                 = $this->objectManager->get(HttpRequest::class);
        $this->checkUserCreateObserver = $this->objectManager->get(CheckUserCreateObserver::class);
        $this->customerSession         = $this->objectManager->get(CustomerSession::class);
        $this->controllerAction        = $this->objectManager->get(Action::class);
        $this->contactHelper           = $this->objectManager->get(ContactHelper::class);
        $this->messageManager          = $this->objectManager->get(Manager::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL, self::ENABLED, 'store', 'default')
    ]
    public function testExecuteWithValidParametersForNewUser()
    {
        $append      = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 5);
        $this->request->setParams(
            [
                'email' => $append. self::EMAIL,
                'password' => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
                'firstname' => self::FIRST_NAME,
                'lastname' => self::LAST_NAME,
            ]
        );
        $this->checkUserCreateObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $values = $this->contactHelper->getValue();
        $this->assertNotNull($values);
        $this->assertArrayHasKey('lsr_username', $values, 'lsr_username key not found in the array');
        $this->assertArrayHasKey('lsr_id', $values, 'lsr_id key not found in the array');
        $this->assertArrayHasKey('lsr_cardid', $values, 'lsr_cardid key not found in the array');
        $this->assertArrayHasKey('group_id', $values, 'group_id key not found in the array');
        $this->assertArrayHasKey('contact', $values, 'contact key not found in the array');
        $this->assertNotNull($values['lsr_username']);
        $this->assertNotNull($values['lsr_id']);
        $this->assertNotNull($values['lsr_cardid']);
        $this->assertNotNull($values['group_id']);
        $this->assertNotNull($values['contact']);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL, self::ENABLED, 'store', 'default')
    ]
    public function testExecuteWithValidParametersForExistingUser()
    {
        $this->request->setParams(
            [
                'email' => self::EMAIL,
                'password' => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
                'firstname' => self::FIRST_NAME,
                'lastname' => self::LAST_NAME,
            ]
        );
        $this->checkUserCreateObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));
        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
        $values = $this->contactHelper->getValue();
        $customerFormData = $this->customerSession->getCustomerFormData();
        $this->assertNull($values);
        $this->assertNotNull($customerFormData);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL, self::ENABLED, 'store', 'default')
    ]
    public function testExecuteWithInvalidParameters()
    {
        $append      = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);

        $this->request->setParams(
            [
                'email' => $append. $append. self::EMAIL,
                'password' => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
                'firstname' => self::FIRST_NAME,
                'lastname' => self::LAST_NAME,
            ]
        );

        $this->checkUserCreateObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));
        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
        $values = $this->contactHelper->getValue();
        $customerFormData = $this->customerSession->getCustomerFormData();
        $this->assertNull($values);
        $this->assertNotNull($customerFormData);
    }

    /**
     * @magentoAppIsolation enabled
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
        $this->checkUserCreateObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));
        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) > 0);
        $values = $this->contactHelper->getValue();
        $this->assertNull($values);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testExecuteWithLsrDown()
    {
        $this->request->setParams(
            [
                'email' => self::EMAIL,
                'password' => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
                'firstname' => self::FIRST_NAME,
                'lastname' => self::LAST_NAME,
            ]
        );
        $this->checkUserCreateObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));
        $values = $this->contactHelper->getValue();
        $customerFormData = $this->customerSession->getCustomerFormData();
        $this->assertNull($values);
        $this->assertNull($customerFormData);
    }
}
