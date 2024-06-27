<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use Laminas\Stdlib\Parameters;
use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\ResetPasswordObserver;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\Manager;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;

class ResetPasswordObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $customerSession;
    public $controllerAction;
    public $resetObserver;
    public $contactHelper;
    public $messageManager;
    public $customerFactory;

    protected function setUp(): void
    {
        $this->objectManager    = Bootstrap::getObjectManager();
        $this->request          = $this->objectManager->get(HttpRequest::class);
        $this->resetObserver    = $this->objectManager->get(ResetPasswordObserver::class);
        $this->customerSession  = $this->objectManager->get(CustomerSession::class);
        $this->controllerAction = $this->objectManager->get(Action::class);
        $this->contactHelper    = $this->objectManager->get(ContactHelper::class);
        $this->messageManager   = $this->objectManager->get(Manager::class);
        $this->customerFactory  = $this->objectManager->get(CustomerFactory::class);
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
        $this->request->setParams(
            [
                'password' => self::PASSWORD
            ]
        );
        $this->request->setQuery(
            new Parameters(['id' => self::CUSTOMER_ID])
        );

        $this->controllerAction->getRequest()->setParams(
            [
                'password' => self::PASSWORD
            ]
        );

        $this->fetchAndSetResetCodeInCustomer();

        $this->resetObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
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
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithNonExistentCustomer()
    {
        $this->request->setParams(
            [
                'password' => self::PASSWORD
            ]
        );
        $this->request->setQuery(
            new Parameters(['id' => self::CUSTOMER_ID])
        );

        $this->controllerAction->getRequest()->setParams(
            [
                'password' => self::PASSWORD
            ]
        );

        $this->resetObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
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
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default')
    ]
    public function testExecuteWithRpToken()
    {
        $this->customerSession->setData('rp_token', 123);
        $this->resetObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
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
        $this->resetObserver->execute(new Observer(
            [
                'request' => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $messages = $this->messageManager->getMessages(false)->getItems();
        $this->assertTrue(count($messages) == 0);
    }

    public function fetchAndSetResetCodeInCustomer()
    {
        $search   = $this->contactHelper->searchWithUsernameOrEmail(self::EMAIL);
        $userName = $search->getUserName();
        $result   = $this->contactHelper->forgotPassword($userName);
        $this->setResetCode(self::CUSTOMER_ID, $result);
    }

    public function setResetCode($customerId, $resetCode)
    {
        $customer = $this->customerFactory->create()->load($customerId);
        $customer->setLsrResetcode($resetCode);
        $customer->save();
    }
}
