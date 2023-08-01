<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

class GetPointRateTest extends OmniClientSetupTest
{
    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $response = $this->client->GetPointRate();
        $result   = $response->getResult();
        $this->assertNotNull($result);
        $this->assertNotEmpty($result);
    }
}
