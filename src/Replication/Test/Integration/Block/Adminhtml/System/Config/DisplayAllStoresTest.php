<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Block\Adminhtml\System\Config;

use \Ls\Replication\Block\Adminhtml\System\Config\DisplayAllStores;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class DisplayAllStoresTest extends TestCase
{
    public function testToOptionArray(): void
    {
        /** @var $model DisplayAllStores */
        $model = Bootstrap::getObjectManager()->create(
            DisplayAllStores::class
        );
        $result = $model->toOptionArray();
        $this->assertCount(2, $result);
    }
}
