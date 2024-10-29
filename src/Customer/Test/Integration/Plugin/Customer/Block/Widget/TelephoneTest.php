<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Plugin\Customer\Block\Widget;

use Magento\Customer\Block\Widget\Telephone;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class TelephoneTest extends TestCase
{
    public $block;
    public $fixtures;
    public $objectManager;
    public $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->block           = $this->objectManager->get(
            LayoutInterface::class
        )->createBlock(
            Telephone::class
        );
    }

    public function testBeforeToHtml()
    {
        $output = $this->block->toHtml();
        $this->assertEquals($this->block->getTemplate(), 'Ls_Customer::widget/telephone.phtml');
        $msg = sprintf('Can\'t validate html: %s', $output);
        $ele = [
            "//div[contains(@class, 'telephone')]",
            "//div[contains(@class, 'control')]",
            "//input[contains(@maxlength, '30') and contains(@data-validate, '{\"required\":true,\"maxlength\":30}')]",
        ];
        $eleCount = implode('', $ele);
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath($eleCount, $output),
            $msg
        );
    }
}
