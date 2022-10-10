<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use PHPUnit\Framework\TestCase;
use Laminas\Uri\UriFactory;

/**
 * Class OmniClientSetupTest
 * @package Ls\Omni\Test\Unit\Client\Ecommerce\Operation
 */
class OmniClientSetupTest extends TestCase
{
    /** @var OmniClient */
    public $client;

    protected function setUp(): void
    {
        $baseUrl      = $_ENV['BASE_URL'];
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
        $this->assertNotNull($this->client);
    }

    public function testExecute()
    {
        $pong = $this->client->Ping();
        $this->assertStringContainsString(
            'PONG OK> Successfully connected to [LS Commerce Service DB] & [LS Central DB] & [LS Central WS]',
            $pong->getResult()
        );
    }
}
