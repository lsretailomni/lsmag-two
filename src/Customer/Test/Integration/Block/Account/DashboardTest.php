<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Account;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Block\Account\Dashboard;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class DashboardTest extends TestCase
{
    public $block;
    public $customerSession;
    public $fixtures;
    public $objectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->block         = $this->objectManager->get(
            LayoutInterface::class
        )->createBlock(
            Dashboard::class
        );

        $this->customerSession = $this->objectManager->get(Session::class);
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
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
    public function testClubInformation()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->block->setTemplate('Ls_Customer::club.phtml');

        $output = $this->block->toHtml();
        $account = $this->block->getMembersInfo();
        $this->assertStringContainsString((string)__('Member Information'), $output);
        $this->assertStringContainsString((string)__('Club Name:'), $output);
        $this->assertStringContainsString((string)__('Loyalty Points Earned:'), $output);
        $this->assertStringContainsString((string)__('Loyalty Level:'), $output);
        $this->assertStringContainsString((string)__('Next Level'), $output);

        if ($account->getScheme()->getNextScheme()) {
            $this->assertStringContainsString((string)__('Level:'), $output);
            $this->assertStringContainsString((string)__('Points Needed:'), $output);
            $this->assertStringContainsString((string)__('Benefits of Next Level:'), $output);
        } else {
            $this->assertStringContainsString((string)__('You are at the top level.'), $output);
        }
    }

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
        )
    ]
    public function testClubInformationWithLsrDown()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->block->setTemplate('Ls_Customer::club.phtml');

        $output = $this->block->toHtml();
        $this->assertStringNotContainsString((string)__('Member Information'), $output);
        $this->assertStringNotContainsString((string)__('Club Name:'), $output);
        $this->assertStringNotContainsString((string)__('Loyalty Points Earned:'), $output);
        $this->assertStringNotContainsString((string)__('Loyalty Level:'), $output);
        $this->assertStringNotContainsString((string)__('Next Level'), $output);
        $this->assertStringNotContainsString((string)__('Level:'), $output);
        $this->assertStringNotContainsString((string)__('Points Needed:'), $output);
        $this->assertStringNotContainsString((string)__('Benefits of Next Level:'), $output);
    }
}
