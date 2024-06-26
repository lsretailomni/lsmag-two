<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplPrice;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommPrices;
use \Ls\Omni\Client\Ecommerce\Entity\ReplPriceResponse;

class ReplEcommPriceTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommPrices();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommPrices", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplPriceResponse::class, $result);
        $this->assertNotNull($result->getPrices());
        $this->assertInstanceOf(ArrayOfReplPrice::class, $result->getPrices());
    }
}
