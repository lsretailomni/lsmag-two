<?php

namespace Ls\Omni\Test\Integration\Plugin\Block\Adminhtml\Order\Create\Totals;

use \Ls\Omni\Plugin\Block\Adminhtml\Order\Create\Totals\DefaultTotalsPlugin;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\DataObject;
use Magento\Sales\Block\Adminhtml\Order\Create\Totals\DefaultTotals;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea adminhtml
 */
class DefaultTotalsPluginTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var mixed
     */
    public $defaultTotal;

    /**
     * @var mixed
     */
    public $defaultTotalsPlugin;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager       = Bootstrap::getObjectManager();
        $this->defaultTotal        = $this->objectManager->get(DefaultTotals::class);
        $this->defaultTotalsPlugin = $this->objectManager->get(DefaultTotalsPlugin::class);
    }

    /**
     * @magentoAppIsolation enabled
     *
     * Test Format pricing for ls_points_earn area code
     */
    #[
        AppArea('frontend'),
    ]
    public function testFormatPriceForLsPoints()
    {
        $this->defaultTotal->setTotal($this->defaultTotal)->setCode('ls_points_earn');
        $result = new DataObject();

        $response = $this->defaultTotalsPlugin->afterFormatPrice($this->defaultTotal, $result, '45');

        $this->assertEquals('45.00 points', $response);
    }


    /**
     * @magentoAppIsolation enabled
     *
     *  Test Format pricing for area code other than ls_points_earn
     */
    #[
        AppArea('frontend'),
    ]
    public function testFormatPriceForDifferentAreaCode()
    {
        $this->defaultTotal->setTotal($this->defaultTotal)->setCode('ls_points_earn_1');
        $result = new DataObject();

        $response = $this->defaultTotalsPlugin->afterFormatPrice($this->defaultTotal, $result, '45');

        $this->assertNotEquals('45.00 points', $response);
    }
}
