<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use \Ls\Customer\Observer\LogoutObserver;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

class LogoutObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $logoutObserver;
    public $customerSession;
    public $controllerAction;
    public $event;
    public $fixtures;

    protected function setUp(): void
    {
        $this->objectManager    = Bootstrap::getObjectManager();
        $this->request          = $this->objectManager->get(HttpRequest::class);
        $this->logoutObserver   = $this->objectManager->get(LogoutObserver::class);
        $this->customerSession  = $this->objectManager->get(CustomerSession::class);
        $this->controllerAction = $this->objectManager->get(Action::class);
        $this->event            = $this->objectManager->get(Event::class);
        $this->fixtures         = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
    ]
    public function testExecuteToCheckEmptySession()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->logoutObserver->execute(new Observer(
            [
                'event' => $this->event,
                'controller_action' => $this->controllerAction,
                'request' => $this->request
            ]
        ));

        $this->assertTrue(count($this->customerSession->getData()) === 0);
    }
}
