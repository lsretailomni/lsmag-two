<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\PostLoginObserver;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

class PostLoginObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $postLoginObserver;
    public $customerSession;
    public $fixtures;

    protected function setUp(): void
    {
        $this->objectManager     = Bootstrap::getObjectManager();
        $this->request           = $this->objectManager->get(HttpRequest::class);
        $this->postLoginObserver = $this->objectManager->get(PostLoginObserver::class);
        $this->customerSession   = $this->objectManager->get(CustomerSession::class);
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
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username'   => AbstractIntegrationTest::USERNAME,
                'lsr_id'     => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid' => AbstractIntegrationTest::LSR_CARD_ID
            ],
            'customer'
        )
    ]
    public function testExecuteWithValidParameters()
    {
        $customer = $this->fixtures->get('customer');

        $this->postLoginObserver->execute(new Observer(
            [
                'request'  => $this->request,
                'customer' => $customer
            ]
        ));

        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNotNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertEquals(1, $this->customerSession->getData('isBasketUpdate'));
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
                'lsr_username'   => null,
                'lsr_id'     => null,
                'lsr_cardid' => null
            ],
            'customer'
        )
    ]
    public function testExecuteWithInValidParameters()
    {
        $customer = $this->fixtures->get('customer');
        $this->postLoginObserver->execute(new Observer(
            [
                'request'  => $this->request,
                'customer' => $customer
            ]
        ));

        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertEquals(1, $this->customerSession->getData('isBasketUpdate'));
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        DataFixture(
            CustomerFixture::class,
            [
                'username'   => null,
                'lsr_id'     => null,
                'lsr_cardid' => null
            ],
            'customer'
        )
    ]
    public function testExecuteWithLsrDown()
    {
        $customer = $this->fixtures->get('customer');
        $this->postLoginObserver->execute(new Observer(
            [
                'request'  => $this->request,
                'customer' => $customer
            ]
        ));

        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData('isBasketUpdate'));
    }
}
