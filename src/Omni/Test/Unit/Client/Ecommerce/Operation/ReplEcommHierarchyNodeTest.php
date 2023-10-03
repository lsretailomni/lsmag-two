<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommHierarchyNode;
use \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchyNodeResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplHierarchyNode;

class ReplEcommHierarchyNodeTest extends ReplicationTest
{
    public function testReplEcommHierarchyNode()
    {
        $request = new ReplEcommHierarchyNode();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommHierarchyNode($request);
        $result   = $response->getResult();
        $this->assertInstanceOf(ReplHierarchyNodeResponse::class, $result);
        $this->assertNotNull($result->getNodes());
        $this->assertInstanceOf(ArrayOfReplHierarchyNode::class, $result->getNodes());
    }
}