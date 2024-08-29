<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

class GetPointRateTest extends OmniClientSetupTest
{
    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $response = $this->executeMethod("GetPointRate");
        $result = $response ? $response->getResult() : null;
        $this->assertNotNull($result);
        $this->assertNotEmpty($result);
    }
}
