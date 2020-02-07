<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommItems;
use \Ls\Omni\Client\Ecommerce\Entity\ReplItemResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplItem;

class ReplEcommItemsTest extends ReplicationTest
{
    public function testReplEcommItems()
    {
        $request = new ReplEcommItems();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommItems($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplItemResponse::class, $result);
        $this->assertNotNull($result->getItems());
        $this->assertInstanceOf(ArrayOfReplItem::class, $result->getItems());
    }
}