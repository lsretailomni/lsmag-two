<?php

namespace Ls\Omni\Test\Unit\Client;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use Zend\Uri\UriFactory;

class PingTest extends \PHPUnit\Framework\TestCase
{
    protected $client;

    protected function setUp()
    {
        $baseUrl      = $_ENV['BASE_URL'];
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
    }

    public function testValidateBaseUrl()
    {
        $this->assertNotNull($this->client);
        $pong = $this->client->Ping();
        $this->assertContains('PONG OK> Successfully connected to [LSOmni DB] & [LSCentral DB]', $pong->getResult());
    }

    /**
     * @return array
     */
    public function matchProvider()
    {
        return [[true], [false]];
    }
}