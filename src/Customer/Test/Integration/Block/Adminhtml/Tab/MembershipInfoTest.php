<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Adminhtml\Tab;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Block\Adminhtml\Tab\MembershipInfo;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManager;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class MembershipInfoTest extends TestCase
{
    public $context;
    public $coreRegistry;
    public $customerRepository;
    public $storeManager;
    public $objectManager;
    public $dataObjectProcessor;
    public $block;
    public $fixtures;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->storeManager = $this->objectManager->get(StoreManager::class);
        $this->context = $this->objectManager->get(
            Context::class,
            ['storeManager' => $this->storeManager]
        );

        $this->coreRegistry = $this->objectManager->get(Registry::class);
        $this->customerRepository = $this->objectManager->get(
            CustomerRepositoryInterface::class
        );
        $this->dataObjectProcessor = $this->objectManager->get(
            DataObjectProcessor::class
        );

        $this->block = $this->objectManager->get(
            LayoutInterface::class
        )->createBlock(
            MembershipInfo::class,
            '',
            [
                'context' => $this->context,
                'registry' => $this->coreRegistry
            ]
        );
    }

    public function testGetTabLabel()
    {
        $this->assertEquals(__('LS Central Membership'), $this->block->getTabLabel());
    }

    public function testGetTabTitle()
    {
        $this->assertEquals(__('LS Central Membership'), $this->block->getTabTitle());
    }

    public function testCanShowTab()
    {
        $this->assertTrue($this->block->canShowTab());
    }

    public function testIsHiddenNot()
    {
        $this->assertFalse($this->block->isHidden());
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
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
    public function testMemberShipInformationLabels()
    {
        $customer = $this->fixtures->get('customer');
        $this->loadCustomer($customer->getId());
        $this->block->setTemplate('Ls_Customer::tab/membership_info.phtml');
        $output = $this->block->toHtml();
        $this->assertStringContainsString((string)__('Membership Information'), $output);
        $this->assertStringContainsString((string)__('Member Contact No.:'), $output);
        $this->assertStringContainsString((string)__('Member Card No.:'), $output);
        $this->assertStringContainsString((string)__('Member Username:'), $output);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
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
    public function testMemberInformationValues()
    {
        $customer = $this->fixtures->get('customer');
        $this->loadCustomer($customer->getId());
        $memberInformation = $this->block->getMembershipInfo();
        $this->block->setTemplate('Ls_Customer::tab/membership_info.phtml');
        $output = $this->block->toHtml();
        $this->validateMemberContactNo($output, $memberInformation);
        $this->validateMemberCardNo($output, $memberInformation);
        $this->validateMemberUsername($output, $memberInformation);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => null,
                'lsr_id'       => null,
                'lsr_cardid'   => null,
                'lsr_token'    => null
            ],
            as: 'customer'
        )
    ]
    public function testMemberInformationWithoutLsrInformation()
    {
        $customer = $this->fixtures->get('customer');
        $this->loadCustomer($customer->getId());
        $memberInformation = $this->block->getMembershipInfo();
        $this->block->setTemplate('Ls_Customer::tab/membership_info.phtml');
        $output = $this->block->toHtml();
        $this->validateMemberContactNo($output, $memberInformation, 0);
        $this->validateMemberCardNo($output, $memberInformation, 0);
        $this->validateMemberUsername($output, $memberInformation, 0);
    }

    public function validateMemberContactNo($output, $memberInformation, $type = 1)
    {
        $elementPaths = [
            "//div[contains(@class, 'customer-information')]",
            "//table[contains(@class, 'admin__table-secondary')]",
            sprintf("//td[contains(text(), '%s')]", $memberInformation['lsr_id']),
        ];
        $eleCount = implode('', $elementPaths);

        if ($type) {
            $this->assertEquals(
                1,
                Xpath::getElementsCountForXpath($eleCount, $output),
                sprintf('Can\'t validate member information in Html: %s', $output)
            );
        } else {
            $this->assertNotEquals(
                1,
                Xpath::getElementsCountForXpath($eleCount, $output),
                sprintf('Can\'t validate member information in Html: %s', $output)
            );
        }
    }

    public function validateMemberCardNo($output, $memberInformation, $type = 1)
    {
        $elementPaths = [
            "//div[contains(@class, 'customer-information')]",
            "//table[contains(@class, 'admin__table-secondary')]",
            sprintf("//td[contains(text(), '%s')]", $memberInformation['lsr_cardid']),
        ];
        $eleCount = implode('', $elementPaths);
        if ($type) {
            $this->assertEquals(
                1,
                Xpath::getElementsCountForXpath($eleCount, $output),
                sprintf('Can\'t validate member information in Html: %s', $output)
            );
        } else {
            $this->assertNotEquals(
                1,
                Xpath::getElementsCountForXpath($eleCount, $output),
                sprintf('Can\'t validate member information in Html: %s', $output)
            );
        }
    }

    public function validateMemberUsername($output, $memberInformation, $type = 1)
    {
        $elementPaths = [
            "//div[contains(@class, 'customer-information')]",
            "//table[contains(@class, 'admin__table-secondary')]",
            sprintf("//td[contains(text(), '%s')]", $memberInformation['lsr_username']),
        ];
        $eleCount = implode('', $elementPaths);
        if ($type) {
            $this->assertEquals(
                1,
                Xpath::getElementsCountForXpath($eleCount, $output),
                sprintf('Can\'t validate member information in Html: %s', $output)
            );
        } else {
            $this->assertNotEquals(
                1,
                Xpath::getElementsCountForXpath($eleCount, $output),
                sprintf('Can\'t validate member information in Html: %s', $output)
            );
        }
    }

    /**
     * @param $customerId
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function loadCustomer($customerId)
    {
        /** @var CustomerInterface $customer */
        $customer = $this->customerRepository->getById($customerId);
        $data = ['account' => $this->dataObjectProcessor
            ->buildOutputDataArray($customer, CustomerInterface::class), ];
        $this->context->getBackendSession()->setCustomerData($data);
        $this->coreRegistry->register(RegistryConstants::CURRENT_CUSTOMER_ID, $customer->getId());

        return $customer;
    }
}
