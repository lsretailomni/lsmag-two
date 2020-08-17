<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Client\Ecommerce\Entity\Store;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use PHPUnit\Framework\TestCase;
use Laminas\Uri\UriFactory;

class StoreGetByIdTest extends TestCase
{
    protected function setUp()
    {
        $baseUrl      = $_ENV['BASE_URL'];
        $url          = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri          = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
    }

    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $param    = [
            'storeId' => $_ENV['STORE_ID']
        ];
        $response = $this->client->StoreGetById($param);
        $result   = $response->getResult();
        $this->assertInstanceOf(Store::class, $result);
        $this->assertNotNull($result->getLatitude());
        $this->assertNotNull($result->getLongitude());
        $this->assertNotNull($result->getPhone());
        $this->assertNotNull($result->getStoreHours());
        $this->assertNotNull($result->getAddress());
        $this->assertEquals($_ENV['STORE_ID'], $result->getId());
    }
}
