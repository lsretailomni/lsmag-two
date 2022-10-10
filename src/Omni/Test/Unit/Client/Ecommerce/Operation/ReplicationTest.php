<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use PHPUnit\Framework\TestCase;
use Laminas\Uri\UriFactory;

class ReplicationTest extends TestCase
{
    /** @var OmniClient */
    public $client;

    /** @var array */
    public $params;

    protected function setUp(): void
    {
        $baseUrl      = $_ENV['BASE_URL'];
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
        $this->params = [
            'BatchSize'       => '1000',
            'FullReplication' => '1',
            'LastKey'         => '0',
            'MaxKey'          => '0',
            'StoreId'         => $_ENV['STORE_ID'],
            'TerminalId'      => '0'
        ];
    }

    public function testClient()
    {
        $this->assertNotNull($this->client);
    }
}
