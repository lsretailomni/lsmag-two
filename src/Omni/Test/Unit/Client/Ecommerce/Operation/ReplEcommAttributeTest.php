<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use Ls\Omni\Client\Ecommerce\Entity\ReplEcommAttribute;
use Ls\Omni\Client\Ecommerce\Entity\ReplAttributeResponse;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplAttribute;

class ReplEcommAttributeTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommAttribute();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommAttribute($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplAttributeResponse::class, $result);
        $this->assertNotNull($result->getAttributes());
        $this->assertInstanceOf(ArrayOfReplAttribute::class, $result->getAttributes());
    }
}