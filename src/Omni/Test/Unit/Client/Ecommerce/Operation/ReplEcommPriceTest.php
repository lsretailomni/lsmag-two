<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use Ls\Omni\Client\Ecommerce\Entity\ReplEcommPrices;
use Ls\Omni\Client\Ecommerce\Entity\ReplPriceResponse;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplPrice;

class ReplEcommPriceTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommPrices();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommPrices($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplPriceResponse::class, $result);
        $this->assertNotNull($result->getPrices());
        $this->assertInstanceOf(ArrayOfReplPrice::class, $result->getPrices());
    }
}