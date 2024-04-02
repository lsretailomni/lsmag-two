<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplItem;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommItems;
use \Ls\Omni\Client\Ecommerce\Entity\ReplItemResponse;

class ReplEcommItemsTest extends ReplicationTest
{
    public function testReplEcommItems()
    {
        $request = new ReplEcommItems();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommItems", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplItemResponse::class, $result);
        $this->assertNotNull($result->getItems());
        $this->assertInstanceOf(ArrayOfReplItem::class, $result->getItems());
    }
}
