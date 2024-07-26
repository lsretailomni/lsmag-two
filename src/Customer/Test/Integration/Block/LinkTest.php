<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Block\Link;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Http\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class LinkTest extends TestCase
{
    public $block;
    public $objectManager;
    public $httpContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->block         = $this->objectManager->get(
            LayoutInterface::class
        )->createBlock(
            Link::class
        );
        $this->httpContext = $this->objectManager->get(Context::class);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
    ]
    public function testLoyaltyLink()
    {
        $this->httpContext->setValue(\Magento\Customer\Model\Context::CONTEXT_AUTH, 1, 1);
        $this->block->setTemplate('Ls_Customer::link.phtml');
        $output = $this->block->toHtml();

        $elementPaths = [
            "//li[contains(@class, 'loyalty')]",
            sprintf("//a[contains(text(), '%s')]", __('Loyalty'))
        ];

        $this->validatePaths(
            $output,
            $elementPaths,
            sprintf('Can\'t validate loyalty link: %s', $output)
        );
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
    ]
    public function testLoyaltyLinkLoggedOutCustomer()
    {
        $this->block->setTemplate('Ls_Customer::link.phtml');
        $output = $this->block->toHtml();
        $this->assertEmpty($output);
    }

    public function testLoyaltyLinkWithLsrDown()
    {
        $this->httpContext->setValue(\Magento\Customer\Model\Context::CONTEXT_AUTH, 1, 1);
        $this->block->setTemplate('Ls_Customer::link.phtml');
        $output = $this->block->toHtml();
        $this->assertEmpty($output);
    }

    public function validatePaths($output, $ele, $msg, $expected = 1, $condition = 1)
    {
        $eleCount = implode('', $ele);

        if ($condition == 1) {
            $this->assertEquals(
                $expected,
                Xpath::getElementsCountForXpath($eleCount, $output),
                $msg
            );
        } else {
            $this->assertGreaterThanOrEqual(
                $expected,
                Xpath::getElementsCountForXpath($eleCount, $output),
                $msg
            );
        }
    }
}
