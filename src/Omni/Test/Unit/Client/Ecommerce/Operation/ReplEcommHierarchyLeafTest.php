<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplHierarchyLeaf;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommHierarchyLeaf;
use \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchyLeafResponse;

class ReplEcommHierarchyLeafTest extends ReplicationTest
{
    public function testReplEcommHierarchyLeaf()
    {
        $request = new ReplEcommHierarchyLeaf();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommHierarchyLeaf", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplHierarchyLeafResponse::class, $result);
        $this->assertNotNull($result->getLeafs());
        $this->assertInstanceOf(ArrayOfReplHierarchyLeaf::class, $result->getLeafs());
    }
}
