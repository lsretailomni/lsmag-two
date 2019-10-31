<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommHierarchyLeaf;
use \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchyLeafResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplHierarchyLeaf;

class ReplEcommHierarchyLeafTest extends ReplicationTest
{
    public function testReplEcommHierarchyLeaf()
    {
        $request = new ReplEcommHierarchyLeaf();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommHierarchyLeaf($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplHierarchyLeafResponse::class, $result);
        $this->assertNotNull($result->getLeafs());
        $this->assertInstanceOf(ArrayOfReplHierarchyLeaf::class, $result->getLeafs());
    }
}