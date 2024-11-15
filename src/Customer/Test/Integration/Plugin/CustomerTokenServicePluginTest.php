<?php

namespace Ls\Customer\Test\Integration\Plugin;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea webapi_rest
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class CustomerTokenServicePluginTest extends TestCase
{
    public $fixtures;
    public $objectManager;
    public $customerTokenService;
    /**
     * @var BasketHelper
     */
    public $basketHelper;
    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager        = Bootstrap::getObjectManager();
        $this->customerTokenService = $this->objectManager->get(CustomerTokenServiceInterface::class);
        $this->fixtures             = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->basketHelper         = $this->objectManager->get(BasketHelper::class);
        $this->customerSession      = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession      = $this->objectManager->get(CheckoutSession::class);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        )
    ]
    public function testAfterRevokeCustomerAccessToken()
    {
        $customer = $this->fixtures->get('customer');

        $this->customerTokenService->createCustomerAccessToken(
            $customer->getEmail(),
            AbstractIntegrationTest::PASSWORD
        );

        $this->customerTokenService->revokeCustomerAccessToken($customer->getId());

        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_CUSTOMER_ID));
        $this->assertNull($this->customerSession->getData(LSR::SESSION_GROUP_ID));
        $this->assertNull($this->checkoutSession->getData(LSR::SESSION_CHECKOUT_MEMBERPOINTS));
        $this->assertNull($this->checkoutSession->getQuoteId());
        $this->assertNull($this->checkoutSession->getData($this->basketHelper->getOneListCalculationKey()));
        $this->assertNull($this->checkoutSession->getData(LSR::SESSION_CHECKOUT_CORRECT_STORE_ID));
        $this->assertNull($this->checkoutSession->getData(LSR::SESSION_CHECKOUT_DELIVERY_HOURS));
        $this->assertNull($this->checkoutSession->getData(LSR::SESSION_CHECKOUT_STORE_PICKUP_HOURS));
    }
}
