<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommAttributeValue;
use \Ls\Omni\Client\Ecommerce\Entity\ReplAttributeValueResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplAttributeValue;

class ReplEcommAttributeValueTest extends ReplicationTest
{
    public function testReplEcommAttributeValue()
    {
        $request = new ReplEcommAttributeValue();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommAttributeValue($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplAttributeValueResponse::class, $result);
        $this->assertNotNull($result->getValues());
        $this->assertInstanceOf(ArrayOfReplAttributeValue::class, $result->getValues());
    }
}