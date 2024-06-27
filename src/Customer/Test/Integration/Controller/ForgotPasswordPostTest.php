<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Controller;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Ls\Omni\Helper\ContactHelper;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\JwtUserToken\Api\Data\Revoked;
use Magento\JwtUserToken\Api\RevokedRepositoryInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

class ForgotPasswordPostTest extends AbstractController
{
    private $objectManager;
    public $contactHelper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->contactHelper    = $this->objectManager->get(ContactHelper::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture createCustomerWithCustomAttributesFixture
     * @magentoConfigFixture current_store customer/captcha/enable 0
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default')
    ]

    public function testForgotPasswordWithCustomerExists(): void
    {
        $this->getRequest()->setPostValue(['email' => AbstractIntegrationTest::EMAIL]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('customer/account/forgotPasswordPost');

        $customer = $this->contactHelper->getCustomerByEmail(AbstractIntegrationTest::EMAIL);
        $this->assertNotNull($customer->getData('lsr_resetcode'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store customer/captcha/enable 0
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default')
    ]

    public function testForgotPasswordWithNonExistentCustomerInMagento(): void
    {
        $this->getRequest()->setPostValue(['email' => AbstractIntegrationTest::EMAIL]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('customer/account/forgotPasswordPost');

        $customer = $this->contactHelper->getCustomerByEmail(AbstractIntegrationTest::EMAIL);
        $this->assertNotNull($customer->getData('lsr_resetcode'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store customer/captcha/enable 0
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default')
    ]

    public function testForgotPasswordWithNonExistentCustomerInBothCentralAndMagento(): void
    {
        $this->getRequest()->setPostValue(['email' => '123'. AbstractIntegrationTest::EMAIL]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('customer/account/forgotPasswordPost');

        $customer = $this->contactHelper->getCustomerByEmail(AbstractIntegrationTest::EMAIL);
        $this->assertNull($customer->getData('lsr_resetcode'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store customer/captcha/enable 0
     */
    public function testForgotPasswordWithLsrDown(): void
    {
        $this->getRequest()->setPostValue(['email' => '123'. AbstractIntegrationTest::EMAIL]);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('customer/account/forgotPasswordPost');

        $customer = $this->contactHelper->getCustomerByEmail(AbstractIntegrationTest::EMAIL);
        $this->assertNull($customer->getData('lsr_resetcode'));
    }

    public static function createCustomerWithCustomAttributesFixture()
    {
        $objectManager = Bootstrap::getObjectManager();
        $customer = $objectManager->create(Customer::class);
        /** @var CustomerRegistry $customerRegistry */
        $customerRegistry = $objectManager->get(CustomerRegistry::class);
        /** @var Customer $customer */
        $customer->setWebsiteId(1)
            ->setId(AbstractIntegrationTest::CUSTOMER_ID)
            ->setEmail(AbstractIntegrationTest::EMAIL)
            ->setPassword(AbstractIntegrationTest::PASSWORD)
            ->setGroupId(1)
            ->setStoreId(1)
            ->setIsActive(1)
            ->setPrefix('Mr.')
            ->setFirstname('John')
            ->setMiddlename('A')
            ->setLastname('Smith')
            ->setSuffix('Esq.')
            ->setDefaultBilling(1)
            ->setDefaultShipping(1)
            ->setTaxvat('12')
            ->setGender(0)
            ->setData('lsr_username', AbstractIntegrationTest::USERNAME)
            ->setData('lsr_id', AbstractIntegrationTest::LSR_ID)
            ->setData('lsr_cardid', AbstractIntegrationTest::LSR_CARD_ID);

        $customer->isObjectNew(true);
        $customer->save();
        $customerRegistry->remove($customer->getId());
        /** @var RevokedRepositoryInterface $revokedRepo */
        $revokedRepo = $objectManager->get(RevokedRepositoryInterface::class);
        $revokedRepo->saveRevoked(
            new Revoked(
                UserContextInterface::USER_TYPE_CUSTOMER,
                (int) $customer->getId(),
                time() - 3600 * 24
            )
        );
    }
}
