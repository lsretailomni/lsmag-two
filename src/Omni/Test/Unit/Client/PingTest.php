<?php

namespace Ls\Omni\Test\Unit\Client;

use SoapClient;

class PingTest extends \PHPUnit\Framework\TestCase
{
    protected $client;

    protected function setUp()
    {
        $baseUrl = 'http://10.27.9.39/LSOmniService411';
        $url = implode('/', [$baseUrl, 'UCService.svc']);
        $this->client = new SoapClient(
            $url . '?singlewsdl',
            ['features' => SOAP_SINGLE_ELEMENT_ARRAYS]
        );
    }

    public function testValidateBaseUrl()
    {
        $this->assertNotNull($this->client);
    }
}