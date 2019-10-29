<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use Ls\Omni\Client\Ecommerce\Entity\ReplEcommProductGroups;
use Ls\Omni\Client\Ecommerce\Entity\ReplProductGroupResponse;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplProductGroup;

class ReplEcommProductGroupsTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommProductGroups();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommProductGroups($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplProductGroupResponse::class, $result);
        $this->assertNotNull($result->getProductGroups());
        $this->assertInstanceOf(ArrayOfReplProductGroup::class, $result->getProductGroups());
    }
}