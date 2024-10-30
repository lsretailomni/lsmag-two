<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Block\Adminhtml\System\Config;

use \Ls\Replication\Block\Adminhtml\System\Config\AttributeSets;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AttributeSetsTest extends TestCase
{
    public function testToOptionArray(): void
    {
        /** @var $model AttributeSets */
        $model = Bootstrap::getObjectManager()->create(
            AttributeSets::class
        );
        $result = $model->toOptionArray();
        $this->assertCount(2, $result);
    }
}
